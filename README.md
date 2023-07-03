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
