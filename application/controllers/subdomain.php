﻿<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
header('content-type:text/html; charset=utf-8');
class subdomain extends CI_Controller {

	public function index($domainId) {
	
		$this->load->library('session');
		$this->load->helper('url');
		$this->load->helper('security');
		
		$data = Array();
		$domain = Array();
		$data['base'] = $this->config->item('base_url');
		
		if(strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 6.0')){
			redirect($this->config->item('base_url').'index.php/error', 'refresh'); 
		}
		if(strpos($_SERVER['HTTP_USER_AGENT'],'MSIE 7.0')){
			redirect($this->config->item('base_url').'index.php/error', 'refresh');
		}
		
		
		if(""==trim($domainId)){
			redirect($this->config->item('base_url').'index.php/error', 'refresh');
		}
		
		
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}
		$data['domain'] = $this->db->query("select * from domain where category='D' and `domain_id`='".$domainId."'")->result_array();
		if (0==$this->db->affected_rows()) {
			redirect($this->config->item('base_url').'index.php/error', 'refresh');
		} else {
		
			$nodeip = $this->db->query("select NodeIP from node_info")->result_array();
			$data["nodeip"] = $nodeip;
			$this->load->view('subdomain',$data);
		}
		
	}
	
	function query_dnspod_subdomain() {
		session_start();
	
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		
		$dnspod_domainId = $_POST["dnspod_domainId"];
		//$dnspod_domainId = "1790860";
		if(""==trim($dnspod_domainId)){
			$odata["code"] = 0;
			$odata["message"] = "DNSPod域名编号不可以为空";
			echo json_encode($odata);
			exit;
		}
		
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}
		$domain = $this->db->query("select * from domain where domain_id='".$dnspod_domainId."' and category='D'")->result_array();
		if ($this->db->affected_rows()==0) {
			redirect($this->config->item('base_url').'index.php/error', 'refresh');
		}
		
		$domainName = $domain[0]["domain_name"];
		
		$this->load->model("Dnsapi_model","dnspodapi");
		$response = $this->dnspodapi->api_call("Record.List", array('domain_id' => $dnspod_domainId));
		if (!$response || 1!=$response["status"]["code"]) {
			$odata["code"] = 0;
			$odata["message"] = $response["status"]["message"];
			echo json_encode($odata);
			exit;
		}
		
		foreach ($response['records'] as $id => $record) {
			if ("A"==$record['type']&&"1"==$record['enabled']) {
				$this->db->query("select 1 from domain where record_id='".$record['id']."'");
				if (0==$this->db->affected_rows()) {
					$this->db->query("insert into domain (domain_id,record_id,domain_name,sub_domain,parent_id,domain_ip,node_ip,status,category,record_line) values ('','".$record['id']."','".$domainName."','".$record['name']."','".$dnspod_domainId."','".$record['value']."','',1,'D','".$record['line']."')");
				}
			}
		}
		
		/*
		$temps = array();
		foreach ($response['records'] as $id => $record) {
			if ("A"==$record['type']&&"1"==$record['enabled']) {				
				$temps[] = $record['name'];
			}
		}
		$temps=array_count_values($temps);
		
		$rnames = array();
		foreach ($temps as $temp => $rcount){
			if ($rcount==1) {
				$rnames[] = $temp;
			}
		}

		foreach ($response['records'] as $id => $record) {
			if ("A"==$record['type']&&"1"==$record['enabled']&&in_array($record['name'], $rnames)) {
				$this->db->query("select 1 from domain where parent_id='".$dnspod_domainId."' and domain='".$record['name']."'");
				if (0==$this->db->affected_rows()) {
					$this->db->query("insert into domain (dnspod_id,dnspodsub_id,domain,parent_id,domain_ip,node_ip,status,category,line) values ('','".$record['id']."','".$record['name']."','".$dnspod_domainId."','".$record['value']."','',1,'D','".$record['line']."')");
				}
			}
		}
		*/
		$list = $this->db->query("select * from domain where category='D' and parent_id='".$dnspod_domainId."' order by record_id asc")->result_array();
		echo json_encode($list);
	}
	
	function append_dnspod_subdomain () {
	
		session_start();
		
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		
		$domain_id = $_POST["domain_id"];
		if(""==trim($domain_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod域名编号不可以为空"));
			exit;
		}
		
		$domain_name = $_POST["domain_name"];
		if(""==trim($domain_name)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod域名名称不可以为空"));
			exit;
		}
		
		$sub_domain = $_POST["sub_domain"];
		if(""==trim($sub_domain)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod二级域名不可以为空"));
			exit;
		}
		
		$record_type = $_POST["record_type"];
		if(""==trim($record_type)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录类型不可以为空"));
			exit;
		}
		
		$record_line = $_POST["record_line"];
		if(""==trim($record_line)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录线路不可以为空"));
			exit;
		}
		
		$domain_ip = $_POST["domain_ip"];
		if(""==trim($domain_ip)){
			echo json_encode(array("code"=>0,"message"=>"源站IP不可以为空"));
			exit;
		}
		
		$value = $_POST["value"];
		if(""==trim($value)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录值不可以为空"));
			exit;
		}
		
		$mx = $_POST["mx"];
		if(""==trim($mx)){
			$mx = "5";
		}
		$ttl = $_POST["ttl"];
		if(""==trim($ttl)){
			$ttl = "600";
		}
	
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}
		$this->db->query("SELECT 1 FROM domain WHERE sub_domain='".$sub_domain."' AND node_ip='".$value."' AND parent_id='".$domain_id."'");
		if ($this->db->affected_rows()>0) {
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录值已存在"));
			exit;
		}
	
		$this->load->model("Dnsapi_model","dnspodapi");
		$response = $this->dnspodapi->api_call("Record.Create", array('domain_id' => $domain_id,'sub_domain' => $sub_domain,'record_type' => $record_type,'record_line' => $record_line,'value' => $value,'mx' => $mx,'ttl' => $ttl));
		if (!$response || 1!=$response["status"]["code"]) {
			echo json_encode(array("code"=>0,"message"=>"修改记录失败,请检查DNSPod帐号是否正确！"));
			exit;
		}
		
		$response = $this->dnspodapi->api_call("Record.list", array('domain_id' => $domain_id,'sub_domain' => $sub_domain));
		if (!$response || 1!=$response["status"]["code"]) {
			echo json_encode(array("code"=>0,"message"=>"修改记录失败,请检查DNSPod帐号是否正确！"));
			exit;
		}
		
		foreach ($response['records'] as $id => $record) {
			if ("A"==$record['type'] && $value==$record['value']) {
				$this->db->query("insert into domain (domain_id,record_id,domain_name,sub_domain,parent_id,domain_ip,node_ip,status,category,record_line) values ('','".$record['id']."','".$domain_name."','".$record['name']."','".$domain_id."','".$domain_ip."','".$record['value']."',1,'D','".$record['line']."')");
				
				if($this->db->affected_rows() > 0) {
				
					$dom = $this->db->query("SELECT `id` FROM domain WHERE `record_id`='".$record['id']."' and parent_id='".$domain_id."' and category='D'")->result_array();
				
					$this->load->model("Filewrite_model","filewrite");
					$this->filewrite->cache_all($dom[0]['id'],1,1);
				}
			}
		}
		
		echo json_encode(array("code"=>1,'record_id'=>$record["id"],'host'=>$record['name']));
	}
	
	function modify_dnspod_subdomain () {
		session_start();
		
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}

		$domain_id = $_POST["domain_id"];
		if(""==trim($domain_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod域名编号不可以为空"));
			exit;
		}
		
		$record_id = $_POST["record_id"];
		if(""==trim($record_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录编号不可以为空"));
			exit;
		}
		
		$sub_domain = $_POST["sub_domain"];
		if(""==trim($sub_domain)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod二级域名不可以为空"));
			exit;
		}
		
		$record_type = $_POST["record_type"];
		if(""==trim($record_type)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录类型不可以为空"));
			exit;
		}
		
		$record_line = $_POST["record_line"];
		if(""==trim($record_line)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录线路不可以为空"));
			exit;
		}
		
		$domain_ip = $_POST["domain_ip"];
		if(""==trim($domain_ip)){
			echo json_encode(array("code"=>0,"message"=>"源站IP不可以为空"));
			exit;
		}
		
		$value = $_POST["value"];
		if(""==trim($value)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录值不可以为空"));
			exit;
		}
		
		$mx = $_POST["mx"];
		if(""==trim($mx)){
			$mx = "5";
		}
		$ttl = $_POST["ttl"];
		if(""==trim($ttl)){
			$ttl = "600";
		}
		
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}
		$domain = $this->db->query("SELECT `id` FROM domain WHERE `record_id`='".$record_id."' and parent_id='".$domain_id."' and category='D'")->result_array();
		if ($this->db->affected_rows() == 0) {
			redirect($this->config->item('base_url').'index.php/error', 'refresh');
		}
		
		$this->load->model("Dnsapi_model","dnspodapi");
		$response = $this->dnspodapi->api_call("Record.Modify", array('domain_id' => $domain_id,'record_id' => $record_id,'sub_domain' => $sub_domain,'record_type' => $record_type,'record_line' => $record_line,'value' => $value,'mx' => $mx,'ttl' => $ttl));
		if (!$response || 1!=$response["status"]["code"]) {
			echo json_encode(array("code"=>0,"message"=>"修改记录失败,请检查DNSPod帐号是否正确！"));
			exit;
		}
		
		$this->db->query("UPDATE `domain` SET `record_line`='".$record_line."',`node_ip`='".$value."',`domain_ip`='".$domain_ip."' WHERE `record_id`='".$record_id."'");
		if (0==$this->db->affected_rows()) {
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录编辑失败"));
			exit;
		}

		$this->load->model("Filewrite_model","filewrite");
		$this->filewrite->cache_all($domain[0]['id'],1,1);
		
		echo json_encode(array("code"=>1));
	}
	
	function remove_dnspod_subdomain () {
		session_start();
		
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}

		$domain_id = $_POST["domain_id"];
		//$domain_id = '2337396';
		if(""==trim($domain_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod域名编号不可以为空"));
			exit;
		}
		
		$record_id = $_POST["record_id"];
		//$record_id = '18459548';
		if(""==trim($record_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录编号不可以为空"));
			exit;
		}
		
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}
		$domain = $this->db->query("SELECT * FROM domain WHERE `record_id`='".$record_id."' and parent_id='".$domain_id."' and category='D'")->result_array();
		if (0==$this->db->affected_rows()) {
			redirect($this->config->item('base_url').'index.php/error', 'refresh');
		}
		
		if ("@"==$domain[0]['sub_domain']) {
			$DomainName = $domain[0]['domain_name'];
		} else {
			$DomainName = $domain[0]['sub_domain'].".".$domain[0]['domain_name'];
		}
		
		$this->load->model("Dnsapi_model","dnspodapi");
		$response = $this->dnspodapi->api_call("Record.Remove", array('domain_id' => $domain_id,'record_id' => $record_id));
		if (!$response || 1!=$response["status"]["code"]) {
			echo json_encode(array("code"=>0,"message"=>"修改记录失败,请检查DNSPod帐号是否正确！"));
			exit;
		}
		
		$this->db->query("DELETE FROM `domain` WHERE `parent_id`='".$domain_id."' AND `record_id`='".$record_id."' and category='D'");
		if (0==$this->db->affected_rows()) {
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录编辑失败"));
			exit;
		}
	
		$this->db->query("SELECT 1 FROM `domain` WHERE `domain_name`='".$domain[0]['domain_name']."' AND `sub_domain`='".$domain[0]['sub_domain']."' and category='D'");
		if($this->db->affected_rows() == 0) {
			$this->load->model("Filewrite_model","filewrite");
			$this->filewrite->delete_file($DomainName);
			
			$this->db->query("DELETE FROM `domain_conf_loc_conf` WHERE `conf_loc_id` IN (SELECT `locf_id` FROM `domain_conf_loc` WHERE `domain_name`='".$DomainName."')");
			$this->db->query("DELETE FROM `domain_conf_loc` WHERE `domain_name`='".$DomainName."'");
		}

		echo json_encode(array("code"=>1));
	}
	
	
	/*D_monitor_part*/
	function start_modify_monitor(){
		session_start();
		
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}

		$domain_id = $_POST["domain_id"];
		//$domain_id = '2337396';
		if(""==trim($domain_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod域名编号不可以为空"));
			exit;
		}
		
		$dnspod_domain =  $_POST["dnspod_domain"];
		if(""==trim($dnspod_domain)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod监控主机头不可以为空"));
			exit;
		}
		
		$record_id = $_POST["record_id"];
		if(""==trim($record_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录编号不可以为空"));
			exit;
		}
		
		
		$monitor_interval = $_POST["monitor_interval"];
		if(""==trim($monitor_interval)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod监控间隔不可以为空"));
			exit;
		}
		
		$bak_ip = $_POST["bak_ip"];
		if(""==trim($bak_ip)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod宕机备用不可以为空"));
			exit;
		}
		
		$host = $_POST["host"];
		if(""==trim($host)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod监控主机头不可以为空"));
			exit;
		}
		
		
		$this->load->model("Dnsapi_model","dnspodapi");
		
		if(!isset($_POST["monitor_id"])){
			$response = $this->dnspodapi->api_call("Monitor.Create", array('domain_id' => $domain_id,'record_id' => $record_id,'port'=>80,'monitor_interval'=>$monitor_interval,'host'=>$host.'.'.$dnspod_domain,'monitor_type'=>'http','monitor_path'=>'/','points'=>'random','bak_ip'=>$bak_ip,'sms_notice'=>' me,share','email_notice'=>'me,share','less_notice'=>'yes'));
			if (!$response || 1!=$response["status"]["code"]) {
				echo json_encode(array("code"=>0,"message"=>$response["status"]["message"]));
				exit;
			}else{
				$this->db->query("update domain SET d_status=1 ,bak_ip='".$bak_ip."', monitor_id='".$response["monitor"]["monitor_id"]."',monitor_interval='".$monitor_interval."' where record_id=".$record_id."");
				echo json_encode(array("code"=>1,"d_status"=>1,"message"=>$response["status"]["message"],"monitor_id"=>$response["monitor"]["monitor_id"]));
				exit;
			}
		}else{
			$monitor_id = $_POST['monitor_id'];
			$response = $this->dnspodapi->api_call("Monitor.Modify", array('monitor_id'=>$monitor_id,'port'=>80,'monitor_interval'=>$monitor_interval,'host'=>$host.'.'.$dnspod_domain,'monitor_type'=>'http','monitor_path'=>'/','points'=>'random','bak_ip'=>$bak_ip,'sms_notice'=>' me,share','email_notice'=>'me,share','less_notice'=>'yes'));
			if (!$response || 1!=$response["status"]["code"]) {
				echo json_encode(array("code"=>0,"message"=>$response["status"]["message"]));
				exit;
			}else{
				$this->db->query("update domain SET d_status=1 ,bak_ip='".$bak_ip."' ,monitor_interval='".$monitor_interval."' where record_id=".$record_id."");
				echo json_encode(array("code"=>1,"d_status"=>1,"message"=>$response["status"]["message"],"monitor_id"=>$response["monitor"]["monitor_id"]));
				exit;
			}
		}
	}
	
	function remove_dnspod_monitor(){
		session_start();
		
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}

		$monitor_id = $_POST["monitor_id"];
		if(""==trim($monitor_id)){
			echo json_encode(array("code"=>0,"message"=>"D监控ID不可以为空"));
			exit;
		}
		
		$record_id = $_POST["record_id"];
		if(""==trim($record_id)){
			echo json_encode(array("code"=>0,"message"=>"DNSPod记录编号不可以为空"));
			exit;
		}
		
		$this->load->model("Dnsapi_model","dnspodapi");
		$response = $this->dnspodapi->api_call("Monitor.Remove", array('monitor_id'=>$monitor_id));
		if (!$response || 1!=$response["status"]["code"]) {
			echo json_encode(array("code"=>0,"message"=>$response["status"]["message"]));
			exit;
		}else{
			$this->db->query("update domain SET d_status=0 ,bak_ip='', monitor_id='',monitor_interval=0  where record_id=".$record_id."");
			echo json_encode(array("code"=>1,"d_status"=>0,"message"=>$response["status"]["message"]));
			exit;
		}
	
	}
	
	function get_monitor_list_latestone(){
		session_start();
		
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		$this->load->database();
		$boss_check = $this->db->query("SELECT tell from users where user_mail = '{$this->session->userdata('cdn_Name')}'")->row()->tell;
		if($boss_check != 1){
			exit('Access Denied');
		}
	
		$record_id=$_POST["record_id"];
		
		$this->load->model("Dnsapi_model","dnspodapi");
		$response = $this->dnspodapi->api_call("Monitor.List",array());
		$res = $response["monitors"][0];
		
		$sqlRes = $this->db->query("update domain SET d_status=1 ,bak_ip='".$res["bak_ip"]."', monitor_id='".$res["monitor_id"]."',monitor_interval='".$res["monitor_interval"]."'  where record_id=".$record_id."");
		
		echo json_encode(array("code"=>1,"d_status"=>1,"message"=>"成功补充D监控记录"));
		exit;
	}
	
		function list_monitor_list(){
		session_start();
		
		$this->load->library("session");
		$this->load->helper("url");
		$this->load->helper("security");
		
		$this->load->model("Dnsapi_model","dnspodapi");
		$response = $this->dnspodapi->api_call("Monitor.List",array());
		print_r($response);
	}
}
