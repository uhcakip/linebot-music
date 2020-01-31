## Music Bot

### 說明
- 結合 Line Bot 及 KKBox
- 點選 Rich Menu 限定搜尋範圍 ( 歌手名、專輯名、歌名 ) 並輸入關鍵字搜尋，機器人會回傳對應的音樂資訊 ( Flex Message ) 或試聽音檔 ( Audio Message )

### 開發環境
- PHP 7.2 
- [Laravel 6.8](https://laravel.com/)

### 額外引入的套件
- [line-bot-sdk-php](https://github.com/line/line-bot-sdk-php) : 用來串接 Line 機器人 
- [OpenAPI-PHP](https://github.com/KKBOX/OpenAPI-PHP) : 用來搜尋音樂資訊 

### Demo 影片
1. 輸入歌曲關鍵字 TT ( 已事先選定搜尋範圍為歌曲 )
2. 點選試聽，機器人回傳試聽檔
3. 點選前往下載，導到試聽檔連結，Android 手機可點選下載
4. 切換搜尋範圍為歌手，並輸入關鍵字 twice，機器人回傳歌手資訊
5. 點選顯示相關歌手專輯，機器人回傳隨機 5 張專輯資訊
6. 點選專輯歌曲，機器人回傳隨機 5 首該專輯歌曲

<img src="https://github.com/uhcakip/linebot-music/blob/master/storage/linebot-music-demo.gif?raw=true" width="55%">