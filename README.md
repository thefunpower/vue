# vue 

## 初始化

vue 3
~~~
$vue =  new Vue;
$vue->version = 3;
~~~

vue 2
~~~
$vue =  new Vue; 
~~~


### 时间区间

~~~
$vue->search_date = [
  '今天',
  '昨天',
  '本周',
  '上周',
  '上上周',
  '本月',
  '上月',
  '上上月',
  '本年'=>'今年', 
  '上年'=>'去年',
  '上上年',
  '最近一个月',
  '最近两个月',
  
  '最近三个月',
  '第一季度', 
  '第二季度', 
  '第三季度', 
  '第四季度', 
];
$vue->add_date();

~~~

`search_date` 以 `key`=>`value`形式存在，`key`是显示的时间，`value`是显示的标题


~~~
<el-date-picker   v-model="where.date" value-format="yyyy-MM-dd" :picker-options="pickerOptions" size="medium" type="daterange" range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期">
</el-date-picker>
~~~

![演示时间效果](/tests/date1.png "演示时间效果") 

### data 
~~~
$vue->data('text','welcome');
~~~

### created

~~~ 
$vue->created(['load()']);
$vue->method('load()',"

");

~~~

### mounted 
~~~
$vue->mounted("a","
  alert(2);
")
~~~
其中`a`是`key`

### watch

~~~
$vue->watch("page(new_val,old_val)","
  console.log('watch');
  console.log(old_val);
  console.log(new_val);
")
~~~

~~~ 
$vue->watch("where.per_page","
  handler(new_val,old_val){
    console.log('watch');
    console.log(old_val);
    console.log(new_val);
  },  
"); 
~~~

~~~
$vue->watch("where","
  handler(new_val,old_val){
    console.log('watch');
    console.log(old_val.per_page);
    console.log(new_val.per_page);
  }, 
  deep: true
");
~~~


底部加入
~~~
<?php  
if($vue){
?>
<script type="text/javascript">
	<?=$vue->run();?>
</script>
<?php }?> 
~~~

# wangeditor 富文本

如`body`字段

在html中
~~~
<?=$vue->editor()?>
~~~

vue代码

~~~
$vue->editor_method();
$vue->method("edit_form(row)","
    let f = this.field;
    this.form = {};
    for(let r of this.field){
        this.\$set(this.form,r,row[r]);
    } 
    this.is_open = true; 
".$vue->load_editor_edit());
~~~

# 压缩JS
安装 

~~~
yarn add --dev javascript-obfuscator
~~~

配置
~~~
$config['vue_encodejs'] = true;
$config['vue_encodejs_ignore'] = ['/plugins/config/config.php'];
~~~


### 一般函数

每个季度开始、结束时间
~~~
vue_get_jidu_array($year)
~~~

某月的最后一天
~~~
vue_get_last_day($month = '2023-07')
~~~


### 开源协议 

The [MIT](LICENSE) License (MIT)
