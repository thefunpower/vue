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

### 开源协议 

The [MIT](LICENSE) License (MIT)
