<?php
/** Offline WordPress/HTTP doubles only. No WordPress installation, real credentials or network. */
define('ABSPATH', __DIR__ . '/');
class WP_Error {
    private $code; private $message; private $data;
    public function __construct($code, $message = '', $data = null) { $this->code=$code; $this->message=$message; $this->data=$data; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error($v) { return $v instanceof WP_Error; }
$fixture=array();
function reset_fixture() {
    global $fixture;
    $fixture=array('user'=>10,'session'=>'synthetic-wp-session-one','can'=>true,'ssl'=>true,'nonce'=>true,'base'=>'https://platform.example.test/api','org'=>'11111111-1111-4111-8111-111111111111','meta'=>array(),'options'=>array(),'calls'=>array(),'responses'=>array(),'storeFails'=>false,'actions'=>array());
}
function get_current_user_id() { global $fixture; return $fixture['user']; }
function wp_get_session_token() { global $fixture; return $fixture['session']; }
function current_user_can($cap) { global $fixture; return $cap==='manage_options' && $fixture['can']; }
function is_ssl() { global $fixture; return $fixture['ssl']; }
function wp_salt($purpose) { return 'synthetic-only-wordpress-salt-for-offline-tests'; }
function wp_json_encode($v) { return json_encode($v); }
function wp_generate_uuid4() { return bin2hex(random_bytes(16)); }
function is_email($v) { return filter_var($v,FILTER_VALIDATE_EMAIL); }
function wp_unslash($v) { return $v; }
function add_action($name,$handler) { global $fixture; $fixture['actions'][$name]=$handler; }
function check_ajax_referer($action,$name,$die) { global $fixture; return $action==='attention_network_admin_nonce' && $name==='nonce' && $fixture['nonce']; }
function wp_send_json_success($v) { throw new RuntimeException(json_encode(array('ok'=>true,'data'=>$v))); }
function wp_send_json_error($v,$status) { throw new RuntimeException(json_encode(array('ok'=>false,'data'=>$v,'status'=>$status))); }
function get_user_meta($u,$key,$single) { global $fixture; return $fixture['meta'][$u][$key] ?? ''; }
function update_user_meta($u,$key,$value) { global $fixture; if ($fixture['storeFails']) return false; $fixture['meta'][$u][$key]=$value; return true; }
function delete_user_meta($u,$key) { global $fixture; unset($fixture['meta'][$u][$key]); return true; }
function get_option($key,$default='') { global $fixture; return $fixture['options'][$key] ?? $default; }
function add_option($key,$v,$unused='',$autoload='') { global $fixture; if (array_key_exists($key,$fixture['options'])) return false; $fixture['options'][$key]=$v; return true; }
function wp_cache_delete($key,$group) {}
class OfflineWpdb {
    public $options='offline_options';
    public function prepare($sql,...$args) { return $args; }
    public function query($prepared) { global $fixture; list($key,$value)=$prepared; if (($fixture['options'][$key] ?? null)!==$value) return 0; unset($fixture['options'][$key]); return 1; }
}
$wpdb=new OfflineWpdb();
class Attention_Network_API_Client {
    public static function get_api_url() { global $fixture; return $fixture['base']; }
    public static function get_org_id() { global $fixture; return $fixture['org']; }
    public static function valid_uuid($v) { return is_string($v) && preg_match('/^[a-f0-9-]{36}$/D',$v); }
    public static function validate_api_url($url) { return strpos($url,'https://')===0 ? true : new WP_Error('bad_url','Invalid URL'); }
}
function wp_safe_remote_request($url,$args) {
    global $fixture;
    $fixture['calls'][]=array('url'=>$url,'args'=>$args);
    if (!$fixture['responses']) throw new RuntimeException('Unexpected HTTP call in offline fixture');
    $response=array_shift($fixture['responses']);
    return is_callable($response) ? $response($url,$args) : $response;
}
function wp_remote_retrieve_response_code($response) { return $response['status']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function token($type,$expires,$id='one') { return 'fixture.'.rtrim(strtr(base64_encode(json_encode(array('type'=>$type,'exp'=>$expires,'jti'=>$id))),'+/','-_'),'=').'.synthetic'; }
function response($status,$body) { return array('status'=>$status,'body'=>json_encode($body)); }
function tokens($expires=3600,$id='one') { return array('email'=>'qa@example.test','access_token'=>token('access',time()+$expires,$id),'refresh_token'=>token('refresh',time()+86400,$id)); }
function enqueue_login($expires=3600) { global $fixture; $fixture['responses'][]=response(200,tokens($expires)); }
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); }
function error_code($v,$code) { return is_wp_error($v) && $v->get_error_code()===$code; }
require __DIR__.'/includes/class-platform-session.php';
$passed=0;
function run_case($name,$fn) { global $passed; reset_fixture(); $fn(); ++$passed; echo "PASS $name\n"; }

run_case('login uses exact private auth contract, encrypted storage and safe status only',function(){
 global $fixture; enqueue_login(); $status=Attention_Network_Platform_Session::login('qa@example.test','not-a-real-password');
 check($status===array('connected'=>true,'email'=>'qa@example.test','needsLogin'=>false),'Safe login status');
 $stored=json_encode($fixture['meta']); check(strpos($stored,'not-a-real-password')===false && strpos($stored,'fixture.')===false,'No plaintext password/JWT in storage');
 $request=$fixture['calls'][0]; check($request['url']==='https://platform.example.test/api/auth/login','Exact login URL');
 check(json_decode($request['args']['body'],true)['token_mode']===true,'Private body-token transport');
 check($request['args']['redirection']===0 && $request['args']['sslverify']===true && $request['args']['reject_unsafe_urls']===true,'Safe HTTP transport');
 check(Attention_Network_Platform_Session::get_access_token()===tokens()['access_token'],'Cached private token available');
 check(count($fixture['calls'])===1,'No unnecessary refresh');
 check(array_keys(Attention_Network_Platform_Session::status())===array('connected','email','needsLogin'),'No browser token fields');
});
run_case('WordPress user, login session, organization and API base isolate stored tokens',function(){
 global $fixture; enqueue_login(); Attention_Network_Platform_Session::login('qa@example.test','fake');
 foreach(array('user'=>11,'session'=>'other-session','org'=>'22222222-2222-4222-8222-222222222222','base'=>'https://other.example.test/api') as $key=>$value){ $old=$fixture[$key];$fixture[$key]=$value; check(error_code(Attention_Network_Platform_Session::get_access_token(),'platform_sign_in_required'),'Binding '.$key);$fixture[$key]=$old; }
 check(count($fixture['calls'])===1,'No cross-binding network calls');
});
run_case('AES-GCM rejects ciphertext tampering and cross-binding row swaps',function(){
 global $fixture; enqueue_login(); Attention_Network_Platform_Session::login('qa@example.test','fake');
 $key=array_key_first($fixture['meta'][10]); $original=$fixture['meta'][10][$key];$envelope=json_decode($original,true);$envelope['tag']=base64_encode(str_repeat('x',16));$fixture['meta'][10][$key]=json_encode($envelope);
 check(error_code(Attention_Network_Platform_Session::get_access_token(),'platform_sign_in_required'),'Tamper rejected');
 $fixture['meta'][10][$key]=$original;$fixture['session']='other-session';
 $method=new ReflectionMethod('Attention_Network_Platform_Session','context');$method->setAccessible(true);$other=$method->invoke(null);$fixture['meta'][10][$other['meta']]=$original;
 check(error_code(Attention_Network_Platform_Session::get_access_token(),'platform_sign_in_required'),'Cross-row swap rejected');
});
run_case('refresh serializes rotated token use and blocks a concurrent request',function(){
 global $fixture; enqueue_login(5);Attention_Network_Platform_Session::login('qa@example.test','fake');
 $fixture['responses'][]=function($url,$args){check(error_code(Attention_Network_Platform_Session::get_access_token(),'platform_session_busy'),'Concurrent request waits instead of double refresh');check(str_ends_with($url,'/auth/refresh'),'Refresh endpoint');return response(200,tokens(3600,'rotated'));};
 $actual=Attention_Network_Platform_Session::get_access_token();check($actual===tokens(3600,'rotated')['access_token'],'Rotated token returned');
 check(Attention_Network_Platform_Session::get_access_token()===$actual && count($fixture['calls'])===2,'Next request uses persisted rotation');
});
run_case('malformed encrypted envelopes and incomplete auth responses fail closed without plaintext fallback',function(){
 global $fixture;enqueue_login();Attention_Network_Platform_Session::login('qa@example.test','fake');$key=array_key_first($fixture['meta'][10]);
 foreach(array(json_encode(array('v'=>1,'iv'=>array(),'tag'=>'','ciphertext'=>'')),str_repeat('x',65537),'not-json') as $bad){$fixture['meta'][10][$key]=$bad;check(error_code(Attention_Network_Platform_Session::get_access_token(),'platform_sign_in_required'),'Malformed envelope rejected');}
 $fixture['meta']=array();$fixture['responses'][]=response(200,array('access_token'=>'not-a-jwt','refresh_token'=>'not-a-jwt'));
 check(error_code(Attention_Network_Platform_Session::login('qa@example.test','fake'),'platform_session_unconfirmed'),'Incomplete token response rejected');check(empty($fixture['meta']),'No unconfirmed storage');
});
run_case('unconfirmed refresh retains retryable ciphertext; rejected refresh requires new login',function(){
 global $fixture;enqueue_login(5);Attention_Network_Platform_Session::login('qa@example.test','fake');$old=$fixture['meta'];
 $fixture['responses'][]=response(503,array('error'=>'unavailable'));check(error_code(Attention_Network_Platform_Session::get_access_token(),'platform_session_unconfirmed'),'503 preserved');check($fixture['meta']===$old,'Session retained');
 $fixture['responses'][]=response(401,array('error'=>'invalid'));check(error_code(Attention_Network_Platform_Session::get_access_token(),'platform_sign_in_required'),'401 requires login');check(!Attention_Network_Platform_Session::status()['connected'],'Rejected credential retired');
});
run_case('logout503 does not claim success or clear tokens; retry confirms remote logout',function(){
 global $fixture;enqueue_login();Attention_Network_Platform_Session::login('qa@example.test','fake');$old=$fixture['meta'];
 $fixture['responses'][]=response(503,array('error'=>'LogoutNotConfirmed'));$result=Attention_Network_Platform_Session::logout();check(is_wp_error($result) && $result->get_error_data()['status']===503,'Unconfirmed logout surfaces503');check($fixture['meta']===$old,'Unconfirmed logout retained session');
 $fixture['responses'][]=response(200,array('success'=>true));check(Attention_Network_Platform_Session::logout()['needsLogin'],'Confirmed logout');check(!Attention_Network_Platform_Session::status()['connected'],'Local session removed after confirmation');
});
run_case('changed configuration during login cannot store or expose retired tokens',function(){
 global $fixture;$fixture['responses'][]=function(){global $fixture;$fixture['org']='22222222-2222-4222-8222-222222222222';return response(200,tokens());};$fixture['responses'][]=response(200,array('success'=>true));
 check(error_code(Attention_Network_Platform_Session::login('qa@example.test','fake'),'platform_session_changed'),'Retired login rejected');check(empty($fixture['meta']),'No retired storage');check(count($fixture['calls'])===2 && str_ends_with($fixture['calls'][1]['url'],'/auth/logout'),'New orphan session retirement attempted');
});
run_case('storage failure never returns new token and attempts to retire issued credentials',function(){
 global $fixture;enqueue_login();$fixture['storeFails']=true;$fixture['responses'][]=response(200,array('success'=>true));
 check(error_code(Attention_Network_Platform_Session::login('qa@example.test','fake'),'platform_session_storage_unavailable'),'Storage error');check(empty($fixture['meta']) && count($fixture['calls'])===2,'No plaintext fallback');
});
run_case('expired lock is replaced atomically; old owner cannot delete the new lock',function(){
 global $fixture;$method=new ReflectionMethod('Attention_Network_Platform_Session','context');$method->setAccessible(true);$ctx=$method->invoke(null);$old=json_encode(array('owner'=>'old','expires'=>time()-1));$fixture['options'][$ctx['lock']]=$old;enqueue_login();
 $fixture['responses'][0]=function(){global $fixture;$key=array_key_first($fixture['options']);$new=$fixture['options'][$key];$method=new ReflectionMethod('Attention_Network_Platform_Session','delete_lock');$method->setAccessible(true);$ctxMethod=new ReflectionMethod('Attention_Network_Platform_Session','context');$ctxMethod->setAccessible(true);$method->invoke(null,$ctxMethod->invoke(null),json_encode(array('owner'=>'old','expires'=>time()-1)));check($fixture['options'][$key]===$new,'Old owner cannot delete new lock');return response(200,tokens());};
 check(!is_wp_error(Attention_Network_Platform_Session::login('qa@example.test','fake')),'Stale lock recovers');check(empty($fixture['options']),'Owned lock released');
});
run_case('admin/nonce/HTTPS/setup gates prevent secret-bearing HTTP requests',function(){
 global $fixture;$fixture['can']=false;check(is_wp_error(Attention_Network_Platform_Session::login('qa@example.test','fake')),'Admin denied');$fixture['can']=true;$fixture['ssl']=false;check(error_code(Attention_Network_Platform_Session::login('qa@example.test','fake'),'platform_https_required'),'HTTPS denied');$fixture['ssl']=true;$fixture['base']='http://private.example.test';check(error_code(Attention_Network_Platform_Session::login('qa@example.test','fake'),'platform_setup_required'),'Unsafe base denied');$fixture['base']='https://platform.example.test/api';$fixture['nonce']=false;
 foreach(array('ajax_login','ajax_logout','ajax_status') as $method){try{Attention_Network_Platform_Session::$method();throw new RuntimeException('Expected AJAX denial');}catch(RuntimeException $e){$value=json_decode($e->getMessage(),true);check(is_array($value) && $value['status']===403,'Nonce denied');}}
 check(empty($fixture['calls']),'No requests through denied gate');Attention_Network_Platform_Session::init();check(count($fixture['actions'])===3,'Only authenticated AJAX hooks');
});
echo "{$passed}/{$passed} offline session cases passed.\n";
