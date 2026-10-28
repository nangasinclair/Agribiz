<?php require 'config.php';$title='Contact Us';$err='';$ok=false;
if($_SERVER['REQUEST_METHOD']==='POST'){chk();$n=trim($_POST['name']??'');$m=trim($_POST['email']??'');$t=trim($_POST['msg']??'');
if(strlen($n)<2||!filter_var($m,FILTER_VALIDATE_EMAIL)||strlen($t)<10)$err='Enter your name, a valid email and a message of at least 10 characters.';else $ok=true;}
require 'header.php';?><h1>Contact us</h1><p>Email info@agribiz.example or send a message below.</p>
<?php if($ok):?><p class="flash">Thank you, we will reply soon.</p><?php else:?>
<?php if($err):?><p class="err"><?=e($err)?></p><?php endif;?>
<form class="box" method="post"><input type="hidden" name="t" value="<?=csrf()?>"><label>Name</label><input name="name" required minlength="2"><label>Email</label><input type="email" name="email" required><label>Message</label><textarea name="msg" rows="4" required minlength="10"></textarea><br><br><button class="btn">Send message</button></form><?php endif;
require 'footer.php';?>
