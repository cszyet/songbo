<?php
namespace app\index\controller;

use think\Controller;
use app\helper;;

class Wechat extends Controller
{
    public function index() {
        //获得几个参数
        $token     = 'weixin';//此处填写之前开发者配置的token
        $nonce     = $_REQUEST['nonce'];
        $timestamp = $_REQUEST['timestamp'];
        $echostr   = isset ($_REQUEST['echostr']) ? $_REQUEST['echostr'] : '';
        $signature = $_REQUEST['signature'];
        //参数字典序排序
        $array = array();
        $array = array($nonce, $timestamp, $token);
        sort($array);
        //验证
        $str = sha1( implode( $array ) );//sha1加密
        //对比验证处理好的str与signature,若确认此次GET请求来自微信服务器，请原样返回echostr参数内容，则接入生效，成为开发者成功，否则接入失败。
        if( $str  == $signature && $echostr ){
           echo  $echostr;
        }
        else{
        file_put_contents ("/opt/lampp/htdocs/runtime/temp/test.txt",var_export(file_get_contents("php://input"),true));
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //接收微信发来的XML数据
        if(!empty($postStr)){
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $fromUsername = $postObj->FromUserName; //请求消息的用户
        $toUsername = $postObj->ToUserName; //"我"的公众号id
        $keyword = trim($postObj->Content); //消息内容
        $time = time(); //时间戳
        $msgtype = 'text'; //消息类型：文本
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
        </xml>";
        if($postObj->MsgType == 'event'){ //如果XML信息里消息类型为event
            if($postObj->Event == 'subscribe'){ //如果是订阅事件
                $contentStr = "感谢关注55海淘返利服务号！\n完成账号绑定即可收到返利进度、提现等活动提醒哦!\n已注册的用户，请<a href='https://m.55haitao.com/my/weixin_login.html'>点击这里</a>绑定\n如还未注册<a href='https://m.55haitao.com/my/login'>点击注册</a>";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgtype, $contentStr);
                echo $resultStr;
                exit();
           }
           else if ($postObj->Event == 'CLICK'){
                $helper = new helper\wxHelper();
                $token = $helper->access_token;
                file_put_contents ("/opt/lampp/htdocs/runtime/temp/test.txt",$postObj->FromUserName."\r\n",FILE_APPEND);
                ignore_user_abort(true);
                ob_start();
                echo 'success';
                header('Connection: close');
                header('Content-Length: ' . ob_get_length());
                ob_end_flush();
                ob_flush();
                flush();
                $this->sendCustom();
                exit();
            }
        }
        else if ($postObj->MsgType == 'image' || $postObj->MsgType == 'voice')
        {
            $this->downloadFile($postObj);
        }

        if($keyword == 'hehe'){
            $contentStr = 'hello world!!!';
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgtype, $contentStr);
            echo $resultStr;
            exit();
        }else{
            $contentStr = $postObj->Event.'输入hehe试试';
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgtype, $contentStr);
            echo $resultStr;
            exit();
        }

    }else {
        echo "";
        exit;
    }
    }
  }
  public function sendCustom() {
        $obj = new helper\wxHelper();
        $token = $obj->access_token;
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$token;

        $msg = [
            'touser'=>'orxw6w4EDd575ePvOjbMHZi_WtEk',
            'msgtype'=>'text',
            'text'=> [
                "content"=>"这是一个测试测试测试赛测试测试",
            ]
        ];
      $json_data = json_encode ($msg,JSON_UNESCAPED_UNICODE);
      $header = [
          'Content-Type'=>'application/json',
          'Content-Length'=>strlen($json_data),
      ];
      $result = $this->send_data($url, $json_data,'','',$header);
      var_dump ($result);

  }
  public function send_data ($url, $post_data = '', $type = '', $compress = '', $header = array(), $cookiejar = '') {
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
private $appid = 'wx68df00e7432ff4f5';
private $appsecret = '79b14c18aa2adcae1a7f6a71cf69eecc';
private $token = 'weixin';
  public function getAccessToken ()
  {
      $filename = '/opt/lampp/htdocs/runtime/temp/token.txt';
      $file_time = filemtime ($filename);
      $interval = 7130;
      $time = time ();
      $content = file_get_contents($filename);
      if ($file_time + $interval <= $time || !$content)
      {
          //获取新的
          $info = $this->http_request('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret);
          $info = json_decode($info, true);

          if (isset ($info['access_token']) && $info['access_token']) {
              $content = $info['access_token'];
          }
          file_put_contents($filename,$content);
      }
      return trim($content);
  }
  private function downloadFile($obj)
  {
      switch ($obj->MsgType)
      {
          case 'image':
              $prefix = 'jpg';
              break;
          case 'voice':
              $prefix = $obj->Format;
              break;
          default:
              $prefix = '';
      }
      if (!$prefix)
      {
            return;
      }
      $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$this->getAccessToken()}&media_id=".$obj->MediaId;
      #$url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=I6TI8Co2PjpRPt6zlwDnjRX5Xsqk4mMITdJr87g0rPifVH1-AvFGlBOhbE3ck1AqXoRKC2CrfX-VB-inI7hDs7843Ampbx9K_HZj_jyOm08YvUENBlc5Cjk84ip1dZELHKViABASVG&media_id=uXPAbFrVN8t3TYUJWzisWLPdA90c62tf0-uqxJyC-LLbTsM_vernxXU0tVZPzmEm";
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_NOBODY, 0);    //对body进行输出。
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $package = curl_exec($ch);

      $filename = time().rand(100,999).".{$prefix}";
      $dirname = "/opt/lampp/htdocs/runtime/temp/test/";
      if(!file_exists($dirname)){
          mkdir($dirname,0777,true);
      }
      file_put_contents($dirname.$filename,$package);
      //求出文件格式
  }
    /**执行http请求
     * @param $url
     * @param string $post_data
     * @param string $compress
     * @param array $header
     * @param string $cookiejar
     * @return bool|mixed
     */
    function http_request($url, $post_data = '', $type = '', $compress = '', $header = array(), $cookiejar = '') {
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
