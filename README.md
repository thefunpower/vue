# vue

~~~
$vue =  new Vue;
$vue->data('text','welcome');
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
