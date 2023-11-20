<?php
/**
 * 用于生成vue2或vue3的JS代码
 */
class Vue
{
    // 2023-11-01 之前的时间将无法在日期字段中选择
    public $start_date = '';
    /**
    * vue版本，默认为2
    * 当为3时，请加载vue3的JS，如 https://unpkg.com/vue@3/dist/vue.global.js
    */
    public $version = 2;
    /*
    * $config['vue_encodejs'] = true;
    * $config['vue_encodejs_ignore'] = ['/plugins/config/config.php'];
    * 依赖 yarn add --dev javascript-obfuscator
    */
    public $encodejs = false;
    public $upload_url = '/admin/upload/index';
    public $opt = [
        'is_editor' => false,
        'is_page'  => false,
        'is_reset' => false,
        'is_add'   => false,
        'is_edit'  => false,
        'is_tree'  => false,
    ];
    public $after_save = [];
    public $editor_timeout = 600;
    public $opt_method = [
        'is_page'   => 'page_method',
        'is_reset'  => 'reset_method',
        'is_add'    => 'add_method',
        'is_edit'   => 'edit_method',
        'is_tree'   => 'tree_method',
        'is_editor' => 'editor_method',
    ];
    public $opt_data = [
        'is_page' => 'page_data',
    ];
    public $page_url;
    public $add_url;
    public $edit_url;
    public $id   = "#app";
    //默认加载方法load()
    public $load_name   = "load";
    public $data = [
        "is_show" => false,
        'where' => "{per_page:20}",
        'lists' => "[]",
        'page'  => "1",
        'total' => 0,
        'form'  => "{}",
        'node'  => "{}",
        'row'   => "{}",
        'loading'=>true,
    ];
    public $page_data = [
        "is_show" => false,
        'where'   => "{per_page:20}",
        'lists'   => "[]",
        'page'    => "1",
        'total'   => 0,
        'form'    => "js:{}",
        'node'    => "js:{}",
        'res'     => "js:{}",
        'loading' => true,
    ];
    public $watch = [];
    public $mounted = [];
    public $created_js = [];
    public $methods    = [];
    public $create_update_load = [];
    public $page_method = [
        'page_size_change(val)' => "this.where.page= 1;this.where.per_page = val;this.load();",
        'page_change(val)'      => "this.where.page = val;this.load();",
    ];
    public $reset_method = [
        'reload()' => "this.where.page = 1;this.loading=true;this.load();",
        'reset()'  => "this.where = {};this.loading=true;this.load();",
    ];
    public $add_method = ''; 
    public $edit_method = '';
    public $tree_field = 'pid';
    public $tree_method = [
        'select_click(data)' => " 
            this.node = data;
            this.form.pid = data.id;
            this.form._pid_name = data.label;
            this.\$refs['pid'].\$el.click();
       "
    ];
    public $data_form;
    //搜索的时间
    public $search_date = [];
    /**
    * construct
    */
    public function __construct(){
        global $config;
        if(isset($config['vue_encodejs'])){
            $this->encodejs = $config['vue_encodejs'];    
        }      
        if(isset($config['vue_version'])){
            $v = $config['vue_version'];
            if(in_array($v,[2,3])){
                $this->version = $v;
            }
        }
        if(function_exists("do_action")){
            $version = $this->version;
            do_action("vue_version",$version);
            if($version && in_array($version,[2,3])){
                $this->version = $version;
            }
        }
    }
    /**
    * form字段 
    */
    public function data_form($key,$val){ 
        $this->data_form[$key] = $val;
    }
    /**
    * 定义data
    * $vue->data("object","js:{}");
    * $vue->data("arr","[]");
    * $vue->data("aa",500);
    * $vue->data("bb",'
    * {
    *     s:1,
    *     ss:3
    * }
    * ');
    * $vue->data("true",true);
    * $vue->data("false",false);
    * $vue->data("json",json_encode(['a'=>1]));
    * $vue->data("json1",['a','b']); 
    */
    public  function data($key, $val)
    {   
        $this->data[$key] = $val;
    }
    /**
    * 解析data
    */
    protected function parse_data($val){  
        if($val == '{}' || $val == '[]'){
            return "js:".$val;
        }
        if(!is_array($val)){
            $is_json = json_decode($val,true); 
            if(is_array($is_json)){
                $val = $is_json;
            } 
        }   
        if(is_array($val)){
            
        }else  if(is_string($val) && substr($val,0,3) != 'js:'){  
            $trim = trim($val);
            $trim = str_replace("\n","",$trim);
            $trim = str_replace(" ","",$trim);
            if(substr($trim , 0,1) == '{' || substr($trim , 0,1) == '['){
               $val = "js:".$val;
            } 
        }        
        return $val;
    }
    /**
    * 支持method
    */
    public  function method($name, $val)
    {
        if(strpos($name,'(') === false){
            $name = $name."()";
        }
        $this->methods[$name] = $val;
    }
    /**
    * 支持watch
    */
    public  function watch($name, $val)
    {
        if(strpos($name,'.') !== false){
            $name = "'".$name."'";
        }
        $this->watch[$name] = $val;
    }

    public function afterSave($val)
    {
        $this->after_save[] = $val;
    }    
    /**
     $vue->mounted('',"js:
         const that = this
         window.onresize = () => {
          return (() => {
            that.height = that.\$refs.tableCot.offsetHeight;
          })()
        }
    ");
     */
    public  function mounted($name, $val)
    {
        $this->mounted[$name] = $val;
    }

    /**
    * 支持created(['load()'])
    */
    public  function created($load_metheds = [])
    {
        foreach ($load_metheds as $v) {
            $this->created_js[] = $v;
        }
    }
    /**
    * 生成vue js代码
    */
    public  function run()
    {
        global $config;
        $this->init();
        $this->data['_vue_message'] = false;
        $data    = php_to_js($this->data);
        $created = "";
        foreach ($this->created_js as $v) {
            $created .= "this." . $v . ";";
        }
        $methods_str = "";
        $watch_str = "";
        $mounted_str = "";
        $br = "\n\t\t\t\t\t";
        $br2 = "\n\t\t\t\t";
        if (!isset($this->methods["load_common()"])) {
            $this->methods["load_common()"] = "js:{}";
        }
        foreach ($this->methods as $k => $v) {
            $v = str_replace("js:","",$v);
            $this->parse_v($k,$v);
            $methods_str .= $br . $k .  php_to_js($v) .",";
        }
        foreach ($this->watch as $k => $v) { 
            $v = str_replace("js:","",$v);
            $this->parse_v($k,$v);
            $watch_str .= $br . $k . php_to_js($v) . ",";
        }
        foreach ($this->mounted as $k => $v) {
            if(is_string($v) && substr($v,0,3) != 'js:'){ 
                $v = "js:".$v.""; 
            }
            $mounted_str .= $br .  php_to_js($v) . "";
        }  
        $js = "
            var _this;
            var app = new Vue({
                el:'" . $this->id . "',
                data:" . $data . ",
                created(){
                    _this = this;
                    " . $created . "
                },
                mounted(){
                    " . $mounted_str . "
                },
                watch: {
                    " . $watch_str . "
                },
                methods:{" . $methods_str . "$br2}
            }); 
        ";  
        if($this->version == 3){
            $js = "
                var _this;
                const { createApp } = Vue;
                var app = createApp({ 
                    data(){ return " . $data . "},
                    created(){
                        _this = this;
                        " . $created . "
                    },
                    mounted(){
                        " . $mounted_str . "
                    },
                    watch: {
                        " . $watch_str . "
                    },
                    methods:{" . $methods_str . "$br2}
                }).mount('".$this->id."'); 
            ";  
        } 
        $vars = '';
        $e = self::$_editor; 
        if($e){
            foreach($e as $name){
                $vars .=" var editor".$name.";\n";
            }    
        }       
        $code = $vars . $js; 
        $name = $this->load_name;
        if($name && $name != 'load'){
            $code = str_replace("this.load()","this.".$name."()",$code);    
        } 
        if($this->encodejs){
            $uri = $_SERVER['REQUEST_URI'];
            $js_file = '/dist/js/vue/'.md5($uri).'.js';
            $output_path = PATH;
            if(defined('WWW_PATH')){
                $output_path = WWW_PATH;
            }
            $js_file_path = $output_path.$js_file;
            $dir = get_dir($js_file_path);
            if(!is_dir($dir)){mkdir($dir,0777,true);} 
            $is_write = true; 
            $ignore_encode = $config['vue_encodejs_ignore']?:['/plugins/config/config.php'];
            foreach($ignore_encode as $v){
                if(strpos($uri,$v) !== false){
                    $is_write = false;
                    break;
                }
            } 
            if($is_write){
                if(!file_exists($js_file_path)){
                    file_put_contents($js_file_path,$code);    
                    $obfuscator_bin = $config['obfuscator']?:PATH.'node_modules/javascript-obfuscator/bin/javascript-obfuscator';
                    $run_cmd = $obfuscator_bin." $js_file_path --output $js_file_path"; 
                    exec($run_cmd);
                }
                return " 
                (function() {
                  var vue_php_auto = document.createElement('script');
                  vue_php_auto.src = '".$js_file."';
                  document.body.insertBefore(vue_php_auto, document.body.lastChild);
                })();"; 
            } else{
                return $code;
            }
        }
        if(function_exists('do_action')){
            do_action("vue",$code);
        }
        return $code;
    }
    /**
    * 解析value
    */
    protected function parse_v(&$k,&$v){ 
        $t_v = trim($v);
        if(strpos($k,'(') === false && substr($k,-1) != ':'){
            $k = $k.':';
        } 
        if(substr($t_v,0,2) == '{{'){
            $v = substr($t_v,2,-2); 
        }
        if(substr($t_v,0,1) != '{'){
            $v = "{".$v."}"; 
        }         
        if(is_string($v) && substr($v,0,3) != 'js:'){ 
            $v = "js:".$v; 
        }
    }
    /**
    * init
    */
    public function init()
    {
        $opt = $this->opt;
        if ($opt['is_page']) {
            $this->created(['load()']);
        }
        $data_form_add = '';
        $data_form_update = '';
        if($this->data_form){
            $form = [];
            foreach($this->data_form as $k=>$v){ 
                $v = $this->parse_data($v);
                $val  = php_to_js($v); 
                $data_form_add.=" 
                     this.\$set(this.form,'".$k."',$val);\n   
                ";
                $data_form_update.="
                    if(!row.$k){
                        this.\$set(this.form,'".$k."',$val);\n    
                    }                    
                "; 
                $form[$k] = $v; 
            }      
            $this->data['form'] = $form;
        }
        $this->add_method = $this->add_method?:[
            "show()" => " 
                 this.is_show = true;
                 this.form = {};".$data_form_add."
                 ".$this->load_editor_add()."
            ",
        ];

        $this->edit_method = $this->edit_method?:[
            "update(row)" => " 
                this.is_show = true;
                this.form = row;  ".$data_form_update."
                ".$this->load_editor_edit()."
            "
        ];

        foreach($this->data as $k=>$vv){  
            $this->data[$k] = $this->parse_data($vv);
        } 
         
        foreach ($opt as $k => $v) {
            if ($v) {
                if ($this->opt_method[$k]) {
                    $method = $this->opt_method[$k];
                    if (method_exists($this, $method)) {
                        $this->$method();
                    }
                    if ($this->$method) {
                        $this->methods = array_merge($this->methods, $this->$method);
                    }
                }
                if ($this->opt_data[$k]) {
                    $data_name = $this->opt_data[$k];
                    $this->data = array_merge($this->$data_name, $this->data);
                }
            }
        }
        $this->crud();
    }


    /**
    * 支持crud 
    */
    public function crud()
    {
        if($this->page_url){
            $this->method('load()', "js:ajax('" . $this->page_url . "',this.where,function(res) { 
                _this.page   = res.current_page;
                _this.total  = res.total;
                _this.lists  = res.data;
                _this.res  = res;
                if(_this.loading){ 
                   _this.loading = false; 
                }
            });");
        }else{
            //$this->method('load()', "js:");
        }
        $after_save = $this->after_save;
        $after_save_str = '';
        if($this->after_save){
            foreach($this->after_save as $v){
                if($v){
                    $v = trim($v); 
                    $after_save_str .= $v;    
                }                
            }
        } 
        if($this->add_url || $this->edit_url){
            $this->method("save()", "js:let url = '" . $this->add_url . "';
                if(this.form.id){
                    url = '" . $this->edit_url . "';
                } 
                ajax(url,this.form,function(res){ 
                        console.log(res);
                        _this.\$message({
                          message: res.msg,
                          type: res.type
                        }); 
                        if(res.code == 0){
                            _this.is_show    = false; 
                            _this.load();
                        }
                        ".$after_save_str."
                }); 
            ");
        }else{
            
        }
        
    }
    /**
    * 编辑器
    */
    public function editor_method()
    { 
        $this->data("editor", "js:{}");
        
        $this->method("weditor()", "js:   
              ".$this->load_editor()."  
        ");
    }
    /**
    * 生成编辑器HTML
    */
    public static $_editor;
    public function editor($name = 'body'){
        self::$_editor[] = $name; 
        return '<div id="'.$name.'editor—wrapper" class="editor—wrapper">
            <div id="'.$name.'weditor-tool" class="toolbar-container"></div>
            <div id="'.$name.'weditor" class="editor-container" ></div>
        </div> ';
    }
    /**
    * 添加
    */
    public function load_editor_add(){
        $e = self::$_editor; 
        if(!$e){
            return;
        }
        $js = '';
        foreach($e as $name){
            $js .="
                setTimeout(function(){
                    editor".$name.".setHtml('');
                },".$this->editor_timeout.");                
            ";
        }
        return $js;
    }
    /**
    * 更新
    */
    public function load_editor_edit(){
        $e = self::$_editor; 
        if(!$e){
            return;
        }
        $js = ''; 
        foreach($e as $name){
            $js .=" 
                let dd_editor".$name." = row.".$name."; 
                setTimeout(function(){
                    editor".$name.".setHtml(dd_editor".$name."); 
                },".$this->editor_timeout."); 
            ";
        }
        return $js;
    }

    /**
    * 加载wangeditor
    */
    public function load_editor(){
            $e = self::$_editor; 
            if(!$e){
                return;
            }
            $js = '';
            foreach($e as $name){
                $js .= " 
                if(editor".$name."){ 
                    editor".$name.".destroy();
                }
                var editorConfig".$name." = {
                    placeholder: '',
                    MENU_CONF: {
                      uploadImage: {
                        fieldName: 'file',server: '".$this->upload_url."?is_editor=1'
                      }
                    }, 
                    onChange(editor) {  
                      _this.form.".$name." = editor.getHtml(); 
                    }
                }; 
                editor = E.createEditor({
                    selector: '#".$name."weditor', 
                    config: editorConfig".$name.",
                    mode: 'simple',  
                }); 
                editor".$name." = editor; 
                var toolbarConfig".$name." = {}; 
                var toolbar".$name." = E.createToolbar({
                    editor,
                    selector: '#".$name."weditor-tool',
                    config: toolbarConfig".$name.",
                    mode: 'simple',  
                });   
                ";    
            } 

            return $js;
    } 
    /**
    日期区间：
    <el-date-picker @change="reload" v-model="where.date" value-format="yyyy-MM-dd" :picker-options="pickerOptions" size="medium" type="daterange" range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期">
    </el-date-picker>

    $date    = g('date'); 
    if ($date[0]) {
        $where['created_at[>]'] = date("Y-m-d 00:00:00", strtotime($date[0]));
    }
    if ($date[1]) {
        $where['created_at[<=]'] =  date("Y-m-d 23:59:59", strtotime($date[1]));
    }  

   */
    public function add_date(){
        $this->addDateTimeSelect();
    } 
    public function reset_date(){
        $vue->method("reset_date()","
            this.pickerOptions = ".php_to_js($this->get_date_area()).";
        ");
    } 
    /**
    * 排序
    * misc('sortable'); 
    * $vue->sort(".sortable1 tbody","_this.form.xcx_banner");
    */
    public function sort($element,$change_obj='lists_sort',$options = []){ 
        $success  = $options['success'];
        $ajax_url = $options['ajax_url']; 
        $sortable = "sortable".mt_rand(1000,9999);
        $this->mounted('',"js:this.".$sortable."();");
        $this->method($sortable."()","js: 
          /**
          * creator wechat: sunkangchina 
          * this is for commercial license,you can't remove it.
          */
          Sortable.create(document.querySelector('".$element."'),{     
            handle:'.handler', 
            onEnd(eve) {   
                  let newIndex = eve.newIndex;
                  let oldIndex = eve.oldIndex;   
                  let list = app.".$change_obj."; 
                  let old = list[oldIndex]; 
                  //删除老数组 
                  list.splice(oldIndex,1);
                  list.splice(newIndex,0,old); 
                  let dd = [];
                  for(let i in list){ 
                    dd.push({id:list[i].id});
                  }
                  ajax('".$ajax_url."',{
                    data:dd,
                    page:app.page,
                    per_page:app.where.per_page,
                    total:app.total,
                    },function(res){
                      ".$success."
                  }); 
            }
          });
        ");
    }
    /**
     * 输出element el-pager
     * 
     * @param $load  load或load()
     * @param $where 分页传参数
     * @param $arr   el-pager参数 ['@size-change'=>'',':page-sizes']
     */
    public function pager($load='load',$where = 'where',$arr = [])
    {
        if(!$arr['@size-change']){
            $arr['@size-change'] = 'page_size_change';
        } 
        if(substr($load,-1) != ')'){
            $load = $load."()";
        }
        $this->method($arr['@size-change']."(val)"," 
            this.".$where.".page= 1;
            this.".$where.".per_page = val;
            this.".$load.";
         ");
 
        if(!$arr['@current-change']){
            $arr['@current-change'] = 'page_change';
        }
        $this->method($arr['@current-change']."(val)"," 
            this.".$where.".page = val;
            this.".$load.";
        ");
        if(!$arr[':page-sizes']){
            $arr[':page-sizes'] = json_encode(page_size_array());
        }
        if(!$arr[':current-page']){
            $arr[':current-page'] = $where.'.page';
        }
        if(!$arr[':page-size']){
            $arr[':page-size'] = $where.'.per_page';
        }
        if(!$arr['layout']){
            $arr['layout'] = 'total, sizes, prev, pager, next, jumper';
        }
        if(!$arr[':total']){
            $arr[':total'] = 'total';
        }
        if(!$arr['background']){
            $arr['background'] = '';
        }
        
        $attr = '';
        foreach($arr as $k=>$v){
            if($v){
                $attr .= $k."='".$v."' ";
            }else{
                $attr .= $k.$v." ";    
            }   
            $attr .= "\n";           
        }  
        return '<el-pagination '.$attr.'></el-pagination>'."\n" ;
    }

    public  function addDateTimeSelect()
    {
        $this->data['pickerOptions'] = $this->get_date_area();
    }
    protected function get_date_range_flag($a,$b,$allow_1){
        if (!$allow_1 ||  ($allow_1 && $a>=$allow_1 && $b >= $allow_1)){
            return true;
        }
    }
    /**
    * 设置时间选择区间
    */ 
    protected function get_date_area(){ 
        $search_date = $this->search_date;
        $allow_1 = $this->start_date; 
        $arr['今天'] = "
            let start = new Date('".date('Y-m-d',time())."'); 
            let end  = new Date('".date('Y-m-d',time())."'); 
            picker.\$emit('pick', [end, end]);
        ";
        $arr['昨天'] = "
            let start = new Date('".date('Y-m-d',time()-86400)."'); 
            let end  = new Date('".date('Y-m-d',time()-86400)."'); 
            picker.\$emit('pick', [start, start]);
        ";
        $a = date("Y-m-d",strtotime('this week'));
        $b = date('Y-m-d',time());
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['本周'] = "
                let start = new Date('".$a."'); 
                let end   = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            ";      
        } 
        $a = date("Y-m-d",strtotime('last week monday'));
        $b = date('Y-m-d',strtotime('-10 second',strtotime('last week sunday +1 day')));
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['上周'] = "
                let start = new Date('".$a."'); 
                let end   = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            ";
        }
        $a = date("Y-m-d",strtotime('-2 weeks',strtotime('monday this week')));
        $b = date('Y-m-d',strtotime('-1 week -1 second',strtotime('monday this week')));
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['上上周'] = "
                let start = new Date('".$a."'); 
                let end   = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            ";
        }
        $a = date("Y-m-01");
        $b = date("Y-m-d");
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['本月'] = "
                let start = new Date('".$a."');
                let end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            ";
        }
        $a = date("Y-m-d",strtotime('first day of last month'));
        $b = date("Y-m-d",strtotime('last day of last month'));
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['上月'] = "
                let start = new Date('".$a."'); 
                let end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            ";
        }
        $a = date("Y-m-d",strtotime('-2 months', strtotime('first day of this month')));
        $b = date("Y-m-d",strtotime('-1 day', strtotime('first day of last month')));
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['上上月'] = "
                let start = new Date('".$a."'); 
                let end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            ";
        }
        $a = date("Y-m-d",strtotime('-1 month')+86400);
        $b = date('Y-m-d');
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['最近一个月'] = "
                let start = new Date('".$a."'); 
                let end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            "; 
        }
        $a = date("Y-m-d",strtotime('-2 month')+86400);
        $b = date('Y-m-d');
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['最近两个月'] = "
                let start = new Date('".$a."'); 
                let end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            "; 
        }
        $a = date("Y-m-d",strtotime('-3 month')+86400);
        $b = date('Y-m-d');
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['最近三个月'] = "
                let start = new Date('".$a."'); 
                let end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            "; 
        }
        $jidu = vue_get_jidu();  
        foreach($jidu as $k=>$v){
            if($v['flag']){
                $arr[$k] = " 
                    picker.\$emit('pick', ['".$v[0]."', '".$v[1]."']);
                ";
            } 
        }
        $a = date("Y-m-d",strtotime('first day of January'));
        $b = date("Y-m-d",strtotime('last day of December'));
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['本年'] = "
                const start = new Date('".$a."');
                const end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            "; 
        }
        $a = date("Y-m-d",strtotime('first day of January last year'));
        $b = date("Y-m-d",strtotime('last day of December  last year'));
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['上年'] = "
                const start = new Date('".$a."');
                const end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            "; 
        }
        $a = date("Y-m-d",mktime(0, 0, 0, 1, 1, date('Y') - 2));
        $b = date("Y-m-d",mktime(23, 59, 59, 12, 31, date('Y') - 2));
        if($this->get_date_range_flag($a,$b,$allow_1)){
            $arr['上上年'] = "
                const start = new Date('".$a."');
                const end = new Date('".$b."'); 
                picker.\$emit('pick', [start, end]);
            "; 
        }
        if($search_date){
            $new_arr = [];
            foreach($search_date as $title=>$k){
                if($arr[$k]){
                    $new_arr[$k] = $arr[$k];
                }else if($arr[$title]){
                    $new_arr[$k] = $arr[$title];
                }
            }
            $arr = $new_arr;
        }
        $js = [];
        foreach($arr as $k=>$v){
            $js[] = [
                'text'=>$k,
                "js:onClick(picker){
                    ".$v."
                }",
            ];
        }
        $str = ['shortcuts'=>$js];   
        if($allow_1){
            $disable_str = "
            return ctime < ".strtotime($allow_1).";
            ";
            $str[] = "js:disabledDate(time){ 
                      let ctime = time.getTime()/1000;
                    ".$disable_str.
                "}";    
        } 
        return $str;
    }  
}

/**
* 季度
* 返回 k=>{0:开始 1:结束 flag:}
*/
function vue_get_jidu($time = ''){
    $time = $time?:now();  
    if(strpos($time,':')!==false){ 
        $time = strtotime($time);
    }        
    $i    = ceil(date("n",$time)/3);
    $arr  = [1=>'一',2=>'二',3=>'三',4=>'四'];
    $year = date("Y",$time); 
    $arr_1 = vue_get_jidu_array($year);
    $new_arr = [];
    $flag = true;
    $ex = true;
    $j = 1;
    foreach($arr as $k=>$v){
        $vv = $arr_1[$k];  
        $vv['flag'] = false;
        if($j <= $i){
            $vv['flag'] = true;
        }
        $new_arr["第".$v."季度"] = $vv;
        $j++;
    }
    return $new_arr;
}
/**
* 每个季度开始、结束时间
*/
function vue_get_jidu_array($year){
    return [
        1=>[$year."-01-01",$year."-03-".vue_get_last_day($year."-03")],
        2=>[$year."-04-01",$year."-06-".vue_get_last_day($year."-06")],
        3=>[$year."-07-01",$year."-09-".vue_get_last_day($year."-09")],
        4=>[$year."-10-01",$year."-12-".vue_get_last_day($year."-12")],
    ];
}
/**
* 某月的最后一天
*/
function vue_get_last_day($month = '2023-07'){
    return date("t", strtotime($month));
}

/**
* vue message
*/
function vue_message(){
    return "  
    if(!app._vue_message){
        app._vue_message = true;
        _this.\$message({duration:1000,type:res.type,message:res.msg,onClose:function(){
            app._vue_message = false;
        }});        
    }
    \n";
}
/**
* loading效果
*/
function vue_loading($name='load',$txt = '加载中'){
    return "const ".$name." = _this.\$loading({
          lock: true,
          text: '".$txt."',
          spinner: 'el-icon-loading',
          background: 'rgba(0, 0, 0, 0.7)'
    }); \n";
}

