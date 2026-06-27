# 公開前チェックリスト

## アップロード前

- `config.php` の管理画面ユーザー名とパスワードを本番用にしている
- `index.php` のお問い合わせ送信後URLは自動生成になっている
- `.htaccess` と各フォルダの `.htaccess` も一緒にアップロードする
- 不要な確認用画像や下書きHTMLは公開しない、または `.htaccess` で見えないようにする
- 既に本番や確認環境で投稿・アップロード済みの画像がある場合、`data/` と `image/member/president/` を空のローカルフォルダで上書きしない

## サーバーで書き込み権限が必要

- `data/`
- `data/news.json`
- `data/regular-members.json`
- `data/support-members.json`
- `data/settings.json`
- `image/news/`
- `image/fv/`
- `image/logo/`
- `image/member/president/`

## アップロード後の確認

- トップページが表示される
- ヘッダー・フッターロゴが表示される
- 最新情報一覧と詳細ページが表示される
- 管理画面にログインできる
- 最新情報を投稿・編集できる
- 画像アップロードができる
- 正会員情報を編集できる
- お問い合わせ送信後に `thanks.php` へ移動する
- `https://公開URL/data/news.json` が表示されない
- `https://公開URL/config.php` が表示されない
