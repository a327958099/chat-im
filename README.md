# chat-im
web即时聊天

基于workerman开发的web端即时通信（可参照workerman安装方式）

请安装redis扩展

消息存储使用redis List队列

支持
会话提醒
会话置顶
离线存储
表情传输
消息撤回
文件传输（开发中）

demo：http://im.skeep.cc


运行方式
cd MessageWorker

debug模式
php start.php start 

守护进程
php start.php start -d

