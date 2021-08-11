# PmmpToLobi
LobiとPmmpで双方向通信を可能にしたプラグインです

Config.yml
```
#ログイン用
Mail: テスト@テスト.gg
Pass: aiueo12345
#メッセージを送信するグループID
sendGroup: https://web.lobi.co/group/  ->  000000000000000000000000000000000000000  <-
getGroup: https://web.lobi.co/group/  ->  000000000000000000000000000000000000000  <-
#メッセージを取得する周期(20 = 1秒)
LoadTime: 10
#一回のループで取得するメッセージ数
LoadThreads: 1
#変更しないでください
UID: 変更しないでください
Time: 変更しないでください
Name: 変更しないでください
#送信メッセージ
JoinMessage: '%nameがサーバーに参加しました！<br>(%now/%max)'
QuitMessage: '%nameがサーバーから退出しました.....<br>(%reason)<br>また来てくださいね！<br>(%now/%max)'
ChatMessage: <%name> %message
SendMessage: '[LOBI] <%name> %message'
ServerStart: サーバーが起動しました！
ServerStop: サーバーが停止しました.....<br>起動までお待ちください！
#メッセージを送信したときの返信メッセージ
ReadMessage: 送信しました
FailureMessage: 送信できませんでした
#シャウト設定
#サーバー起動・停止
ServerMessageShout: true
#プレイヤーのチャット・参加・退出
MessageShout: false
```
このプラグインは「NewDelion」様が公開しているLobiAPIを使用したものです  
URL: https://github.com/NewDelion/LobiAPI-PHP
