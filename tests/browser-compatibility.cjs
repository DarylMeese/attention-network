'use strict';
// Headless, fully controlled DOM fixture. Supply existing Playwright and pinned jQuery paths; no downloads.
const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict');
const args=process.argv.slice(2),value=flag=>args[args.indexOf(flag)+1];
assert(args.includes('--playwright')&&args.includes('--jquery'),'Supply --playwright module and --jquery file.');
const {chromium}=require(path.resolve(value('--playwright'))),root=path.resolve(__dirname,'..');
(async()=>{
  const browser=await chromium.launch({headless:true});let checks=0;
  try{
    const page=await browser.newPage();const errors=[];
    page.on('pageerror',e=>errors.push(e.message));page.on('dialog',d=>d.dismiss());await page.route('**/*',route=>route.abort());
    await page.setContent(`<html><body><div id="attn-menu-app" data-menu-id="058c84dd-c303-4bb1-91da-8728b35ac2b1" data-is-active="0"><span class="attn-menu-name">Existing menu</span><button id="attn-btn-edit-active-menu">Edit menu</button></div>
      <div class="attn-modal-backdrop" id="attn-modal-menu" style="display:none"><div class="attn-modal"><h2 id="attn-menu-modal-title">Menu</h2><button class="attn-modal-close">×</button><form id="attn-form-menu"><input id="attn-menu-field-id" type="hidden"><input id="attn-menu-field-name"><input id="attn-menu-field-active" type="checkbox"><button id="attn-btn-save-menu">Save</button></form></div></div>
      <form id="attn-platform-login"><input id="attn-platform-email" type="email"><input id="attn-platform-password" type="password"><button>Sign in</button></form><button id="attn-platform-logout">Disconnect</button><div id="attn-platform-status"></div>
      <button class="attn-btn-view-lead" id="view-inquiry">View inquiry</button><div id="attn-lead-modal" class="attn-modal-backdrop" style="display:none"><div class="attn-modal"><h2>Inquiry</h2><button class="attn-modal-close">×</button><div id="attn-lead-modal-content"></div><textarea id="attn-lead-internal-notes"></textarea></div></div></body></html>`);
    await page.addScriptTag({content:fs.readFileSync(value('--jquery'),'utf8')});
    await page.evaluate(()=>{
      window.attnAdmin={ajaxUrl:'https://fixture.invalid/admin-ajax.php',nonce:'controlled-wp-nonce',strings:{testing:'Checking',confirmDelete:'Delete?',connectionFailed:'Unavailable'}};
      window.requests=[];window.pending=[];window.xss=0;jQuery.fx.off=true;
      jQuery.post=function(url,data,callback){const d=jQuery.Deferred();window.requests.push({url,data});window.pending.push({d,callback});return d.promise();};
    });
    await page.addScriptTag({content:fs.readFileSync(path.join(root,'assets/js/admin-menu.js'),'utf8')});await page.waitForTimeout(50);
    await page.locator('#attn-btn-edit-active-menu').click();
    assert.equal(await page.locator('#attn-menu-field-active').isChecked(),false);checks++;
    assert.equal(await page.locator('#attn-modal-menu .attn-modal').getAttribute('role'),'dialog');checks++;
    assert.equal(await page.locator('#attn-modal-menu .attn-modal').getAttribute('aria-labelledby'),'attn-menu-modal-title');checks++;
    assert.equal(await page.locator('#attn-menu-field-name').evaluate(e=>e===document.activeElement),true);checks++;
    await page.locator('#attn-btn-save-menu').focus();await page.keyboard.press('Tab');assert.equal(await page.locator('#attn-modal-menu .attn-modal-close').evaluate(e=>e===document.activeElement),true);checks++;
    await page.keyboard.press('Escape');assert.equal(await page.locator('#attn-modal-menu').isVisible(),false);checks++;
    assert.equal(await page.locator('#attn-btn-edit-active-menu').evaluate(e=>e===document.activeElement),true);checks++;
    await page.locator('#attn-platform-email').fill('synthetic@example.test');await page.locator('#attn-platform-password').fill('SYNTHETIC-NOT-A-REAL-PASSWORD');
    await page.locator('#attn-platform-login').evaluate(e=>e.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true})));
    assert.equal(await page.locator('#attn-platform-password').inputValue(),'');checks++;
    const sent=await page.evaluate(()=>window.requests[0].data);assert.equal(sent.action,'attention_network_platform_login');assert.equal(sent.nonce,'controlled-wp-nonce');checks++;
    await page.evaluate(()=>{window.pending[0].d.resolve({success:true,data:{connected:true,email:'synthetic@example.test'}});});assert.match(await page.locator('#attn-platform-status').textContent(),/connected/i);checks++;
    await page.locator('#attn-platform-logout').click();await page.evaluate(()=>{window.pending[1].d.reject({responseJSON:{data:{message:'Disconnect remains unconfirmed; try again.'}}});});assert.match(await page.locator('#attn-platform-status').textContent(),/unconfirmed/);checks++;
    await page.evaluate(()=>{document.querySelector('#view-inquiry').dataset.lead=JSON.stringify({id:'local-test',name:'<script>window.xss=1</script>',phone:'"><img src=x onerror="window.xss=1">',email:'"><img src=x onerror="window.xss=1">',message:'<svg onload=window.xss=1>',internalNotes:'</textarea><script>window.xss=1</script>'});});
    await page.locator('#view-inquiry').click();assert.equal(await page.locator('#attn-lead-modal-content img,#attn-lead-modal-content script,#attn-lead-modal-content svg').count(),0);checks++;
    assert.equal(await page.evaluate(()=>window.xss),0);checks++;
    assert.equal(await page.locator('#attn-lead-modal-content a').count(),2);checks++;
    assert.match(await page.locator('#attn-lead-modal-content').textContent(),/<img/);checks++;
    assert.match(await page.locator('#attn-lead-internal-notes').inputValue(),/<script>/);checks++;
    assert.deepEqual(errors,[]);checks++;
    console.log(JSON.stringify({headless:true,controlledDom:true,checks,passed:true,realApiCalls:0,realEmails:0}));
  }finally{await browser.close();}
})().catch(error=>{console.error(error.message);process.exitCode=1;});
