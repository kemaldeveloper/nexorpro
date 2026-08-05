import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import { rm, writeFile } from 'node:fs/promises';

const require = createRequire(import.meta.url);
const WebSocket = require('ws');
const port = 9755;
const profile = '/tmp/nexor-anchor-audit';
const screenshot = './artifacts/nexor-evidence/06-about-anchor.png';
await rm(profile, { recursive: true, force: true });
const chrome = spawn('/usr/bin/chromium', ['--headless=new','--no-sandbox','--disable-dev-shm-usage','--disable-http2','--disable-background-networking',`--remote-debugging-port=${port}`,`--user-data-dir=${profile}`,'--window-size=1440,1000','about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
for (let i=0;i<50;i++){try{if((await fetch(`http://127.0.0.1:${port}/json/version`)).ok)break;}catch{}await sleep(100);}
const targets=await(await fetch(`http://127.0.0.1:${port}/json/list`)).json();
const ws=new WebSocket(targets.find(target=>target.type==='page').webSocketDebuggerUrl);
await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=reject;});
let id=0;const pending=new Map();const errors=[];
ws.onmessage=({data})=>{const message=JSON.parse(data);if(message.id&&pending.has(message.id)){const call=pending.get(message.id);pending.delete(message.id);message.error?call.reject(new Error(message.error.message)):call.resolve(message.result);}else if(message.method==='Runtime.exceptionThrown')errors.push(message.params.exceptionDetails.text);else if(message.method==='Network.loadingFailed'&&!message.params.canceled)errors.push(message.params.errorText);};
const send=(method,params={})=>new Promise((resolve,reject)=>{const call=++id;const timer=setTimeout(()=>{pending.delete(call);reject(new Error(`${method} timed out`));},20000);pending.set(call,{resolve:value=>{clearTimeout(timer);resolve(value);},reject:error=>{clearTimeout(timer);reject(error);}});ws.send(JSON.stringify({id:call,method,params}));});
const evaluate=async expression=>(await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true})).result.value;
await send('Page.enable');await send('Runtime.enable');await send('Network.enable');
await send('Page.navigate',{url:'https://nexorpro.ru/#about-company-nexor'});await sleep(4500);
const direct=await evaluate(`(()=>{const section=document.querySelector('#about-company-nexor'),r=section?.getBoundingClientRect(),center=document.elementFromPoint(innerWidth/2,innerHeight/2),visibleOverlays=[...document.querySelectorAll('.nexor-modal,.nexor-lightbox,.nexor-mobile-menu,.nexor-mega,.nexor-exit')].filter(x=>!x.hidden&&getComputedStyle(x).display!=='none').map(x=>x.className);return{url:location.href,ready:document.readyState,viewport:[innerWidth,innerHeight],scrollY:Math.round(scrollY),bodyHeight:document.documentElement.scrollHeight,section:section?{top:Math.round(r.top),bottom:Math.round(r.bottom),height:Math.round(r.height),display:getComputedStyle(section).display,visibility:getComputedStyle(section).visibility,text:section.textContent.trim().slice(0,120)}:null,centerElement:center?.tagName+'.'+center?.className,bodyOverflow:getComputedStyle(document.body).overflow,bodyBackground:getComputedStyle(document.body).backgroundColor,visibleOverlays};})()`);
const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});await writeFile(screenshot,Buffer.from(shot.data,'base64'));
await send('Page.navigate',{url:'https://nexorpro.ru/'});await sleep(3500);
const clicked=await evaluate(`(async()=>{const link=[...document.querySelectorAll('header a')].find(a=>a.textContent.trim()==='О компании');if(!link)return{link:false};link.click();await new Promise(r=>setTimeout(r,1000));const section=document.querySelector('#about-company-nexor'),rect=section?.getBoundingClientRect();return{link:true,url:location.href,scrollY:Math.round(scrollY),sectionTop:Math.round(rect?.top||0),sectionHeight:Math.round(rect?.height||0)};})()`);
console.log(JSON.stringify({direct,clicked,errors:[...new Set(errors)],screenshot},null,2));
ws.close();chrome.kill('SIGKILL');chrome.unref();
