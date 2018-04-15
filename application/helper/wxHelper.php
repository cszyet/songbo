<?php
namespace app\helper;
use \Exception;

class wxHelper
{
    public $appid;
    private $appsecret;
    public $access_token;
    public $api_ticket;

    public function __construct() {

        $this->appid = 'wx68df00e7432ff4f5';
        $this->appsecret = '79b14c18aa2adcae1a7f6a71cf69eecc';
        $this->get_access_token();

    }


    /**
     * 通过code获取授权信息
     * @param $code
     * @return mixed
     */
    public function getAuthorization($code){
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    /**
     * 微信授权登录
     * @param $callbackurl
     */
    public function autorize ($callbackurl)
    {
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".urlencode($callbackurl)."&response_type=code&scope=snsapi_userinfo&state=55haitao#wechat_redirect";
        header("Location: $url");//跳转授权页
    }


    public function get_access_token() {
        $model = db("wx_tokens");
        $now = time();
        $this->access_token = file_get_contents("/opt/lampp/htdocs/runtime/temp/token.txt");

        if (!$this->access_token) {
            //获取新的
            $info = $this->http_request('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret);
            $info = json_decode($info, true);


            if (isset ($info['access_token']) && $info['access_token']) {
                file_put_contents("/opt/lampp/htdocs/runtime/temp/token.txt",$info['access_token']);
                $this->access_token = $info['access_token'];
            }
        }

        return $this->access_token;
    }

    public function get_api_ticket() {

        $now = time();
        $model = db("wx_tokens");
        $this->api_ticket = $model->where(['type' => 2, 'expires_time' => ['gt', $now]])->value('kvalue');

        if (!$this->api_ticket) {
            $info = $this->http_request('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$this->access_token.'&type=jsapi');

            $info = json_decode($info, true);

            if ($info['errcode'] == 0 && $info['ticket']) {
                $save_data = [
                    'kvalue' => $info['ticket'],
                    'expires_time' => $now + $info['expires_in'] - 60,
                    'type' => 2,
                ];

                db("wx_tokens")->insert($save_data);
                $this->api_ticket = $info['ticket'];
            }
        }

        return $this->api_ticket;
    }

    /**
     * 产生随机字符串
     * @param int $length
     * @param string $str
     * @return string
     */
    public function createNoncestr($length = 32, $str = "")
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取签名
     * @param array $arrdata 签名数组
     * @param string $method 签名方法
     * @return bool|string 签名值
     */
    public function getSignature($arrdata, $method = "sha1")
    {
        if (!function_exists($method)) {
            return false;
        }
        ksort($arrdata);
        $params = array();
        foreach ($arrdata as $key => $value) {
            $params[] = "{$key}={$value}";
        }
        return $method(join('&', $params));
    }

    /**
     * 网页请求
     * @param $url
     * @param null $data
     * @return mixed
     */
    private function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
    public function http_request($url, $post_data = '', $type = '', $compress = '', $header = array(), $cookiejar = '') {
	if(is_array($post_data)){
		$post_data = http_build_query($post_data);
	}
	$ch = curl_init();
	if (stripos($url, "https://") !== false) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	}

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	$def_header = array(
		'Connection' => 'keep-alive',
		'Cache-Control' => 'no-cache',
		'Pragma' => 'no-cache',
		'User-Agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
		'Accept' => isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '',
		'Referer' => null,
		'Accept-Encoding' => 'gzip,deflate,sdch',
		'Accept-Language' => 'en-US,zh-CN;q=0.8,zh;q=0.6',
		'Accept-Encoding' => 'GBK,utf-8;q=0.7,*;q=0.3'
	);
	if (! empty($header)) {
		foreach ($header as $key => $value) {
			$def_header[$key] = $value;
		}
	}
	$new_header = array();
	foreach ($def_header as $key => $value) {
		if ($value !== null) {
			$new_header[] = $key . ": " . $value;
		}
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $new_header);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

	if ($compress) {
		curl_setopt($ch, CURLOPT_ENCODING, $compress);
	}
	$type = strtolower($type);
	if($type == 'delete'){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	}else if($type == 'put'){
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	}else if($post_data){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	}
	if ($cookiejar) {
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
	}
	if (is_file($cookiejar)) {
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
	}
	$content = curl_exec($ch);
	$status = curl_getinfo($ch);
	curl_close($ch);
	$fls = false;
	if (intval($status["http_code"]) == 200) {
		unset($status);
		return $content;
	} else {
		unset($status);
		return $fls;
	}
}
}
