# 勤怠管理アプリ

Laravelで作成した勤怠管理アプリです。
一般ユーザーと管理者で権限を分離し、基本的な機能を備えたシンプルなアプリです。

---

## 概要

Laravelを用いて開発した勤怠管理アプリです。

一般ユーザーは出退勤管理や勤怠修正申請を行うことができ、管理者はスタッフの勤怠管理や修正申請の承認を行うことができます。

認証機能にはLaravel Fortifyを利用し、メール認証による本人確認にも対応しています。

---

## 使用技術

| 分類 | 技術 | バージョン |
|---|---|---|
| 言語 | PHP | 8.5（Dockerイメージ `php:8.5-fpm`） |
| フレームワーク | Laravel | 13.40.0 |
| 認証 | Laravel Fortify | 1.37.2（認証・メール認証） |
| パッケージ管理 | Composer | 2系（`composer:2` イメージから導入） |
| DB | MySQL | 8.4（Dockerイメージ `mysql:8.4`） |
| Webサーバー | Nginx | 1.30.6（Dockerイメージ `nginx:1.30.6`） |
| View | Blade | - |
| メール確認 | Mailpit | 1.33.1（Dockerイメージ `axllent/mailpit:v1.33.1`） |
| 開発環境 | Docker / Docker Compose | - |

※ 2026年11月時点の動作確認バージョンです。

---

## Docker構成

| サービス | イメージ・ビルド | 公開ポート | 備考 |
|---|---|---|---|
| nginx | `docker/nginx/Dockerfile`（`nginx:1.30.6` ベース） | `80` | zlibをDebian Securityの修正版へ更新 |
| php | `docker/php/Dockerfile`（`php:8.5-fpm` ベース） | なし | 拡張：`pdo_mysql`・`zip`・`intl`・`xdebug` |
| mysql | `docker/mysql/Dockerfile`（`mysql:8.4` ベース） | `127.0.0.1:3306` | 不要な `mysql-shell` を削除 |
| mailpit | `axllent/mailpit:v1.33.1` | `127.0.0.1:8025`（Web UI）<br>`127.0.0.1:1025`（SMTP） | 開発用メール確認 |

```text
.
├── compose.yaml
├── docker
│   ├── mysql
│   │   ├── Dockerfile
│   │   └── my.cnf
│   ├── nginx
│   │   ├── Dockerfile
│   │   └── default.conf
│   └── php
│       ├── Dockerfile
│       └── php.ini
├── docs        # README・ER図・手順書
└── src         # Laravelアプリケーション
```

- MySQLのデータはDockerの名前付きボリューム `db-data-mysql84` に保存されます。
- MySQL・Mailpitのポートは `127.0.0.1` に限定し、同じネットワーク上の他の端末からはアクセスできないようにしています。
- DB管理ツール（phpMyAdmin）は脆弱性対応のため削除しました。DBを確認する場合は、以下のようにMySQLコンテナから接続するか、ホストのMySQLクライアントで `127.0.0.1:3306` に接続してください。

```bash
docker compose exec mysql mysql -u attendance_user -pattendance_pass attendance_db
```

---

## 機能一覧

### 一般ユーザー

- ユーザー登録
- ログイン
- メール認証
- 勤怠登録（出勤・退勤）
- 休憩開始・終了
- 勤怠一覧表示
- 勤怠詳細表示
- 勤怠修正申請
- 修正申請一覧表示

### 管理者

- ログイン
- 日次勤怠一覧表示
- スタッフ一覧表示
- スタッフ別月次勤怠一覧表示
- CSVエクスポート
- 勤怠修正
- 修正申請一覧表示
- 修正申請承認

---

## ディレクトリ構成（app配下）

Laravel公式の標準構成に沿って、役割ごとにフォルダを分けています。

| フォルダ | 役割 |
|---|---|
| `Http/Controllers` | リクエストの受付・レスポンス返却のみを担当（1リソース1コントローラー） |
| `Http/Requests` | FormRequestによるバリデーション |
| `Http/Middleware` | 管理者判定などリクエストの通過可否の判定 |
| `Http/Responses` | Fortifyのレスポンス（登録・ログイン・ログアウト・メール認証後の遷移先）のカスタマイズ |
| `Services` | 業務ロジック |
| `Exceptions` | 業務エラーを表す例外 |
| `Policies` | モデルに対する認可 |
| `Enums` | 勤怠ステータス・ユーザーロール・申請ステータス |
| `Models` | Eloquentモデル |
| `Actions/Fortify` | Fortifyのユーザー登録処理 |

画面は `resources/views/components/attendance` に一般ユーザー・管理者で共通のBladeコンポーネント（月次勤怠一覧の表、勤怠修正フォームなど）をまとめています。

---

## ER図

勤怠情報、休憩情報、修正申請情報を管理するため、以下のテーブル構成としています。

### テーブル設計

- users
- attendances
- break_records
- attendance_correct_requests
- break_correct_requests

---

![ER Diagram](er/er_diagram.png)

---

## 環境構築

### 1. リポジトリをクローン

```bash
git clone https://github.com/nae6/laravel-attendance-app.git
cd laravel-attendance-app
```

### 2. Dockerビルド・起動

```bash
docker compose up -d --build
```

起動状態を確認します。4つのサービス（nginx・php・mysql・mailpit）が `running` になっていれば成功です。

```bash
docker compose ps
```

### 3. Laravel環境構築

#### 1. PHPコンテナに入る

```bash
docker compose exec php bash
```

以降の手順はPHPコンテナ内で実行します。

#### 2. Laravelパッケージのインストール

```bash
composer install
```

#### 3. .env作成

```bash
cp .env.example .env
php artisan key:generate
```

`.env.example` には、Docker構成に合わせた以下の設定が記載済みです。変更した場合は `php artisan config:clear` でキャッシュをクリアしてください。

```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=attendance_db
DB_USERNAME=attendance_user
DB_PASSWORD=attendance_pass

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

#### 4. データベース初期化

MySQLの初回起動直後は初期化に時間がかかるため、接続エラーになった場合は少し待ってから再実行してください。

```bash
php artisan migrate
php artisan db:seed
```

#### 5. 動作確認

ブラウザで以下にアクセスし、ログイン画面が表示されれば環境構築は完了です。

- 一般ユーザー用ログイン画面: <http://localhost/login>
- 管理者用ログイン画面: <http://localhost/admin/login>
- Mailpit: <http://localhost:8025>

### 開発環境の停止・再起動

```bash
# 停止（コンテナを削除。DBのデータはボリュームに残る）
docker compose down

# 再起動
docker compose up -d
```

`docker compose down -v` はDBのボリュームも削除するため、データを残したい場合は実行しないでください。

---

## Composer依存関係の更新

Composerパッケージの更新方法、依存関係の互換性確認、更新後の検証については、[セキュリティ更新手順書](security-update.md) を参照してください。

この手順は、通常の依存関係更新にも利用できます。

---

## セキュリティ検査

PHP・Composer依存パッケージ・Dockerイメージの脆弱性検査は、[セキュリティ更新手順書](security-update.md) に従って実施します。

同手順書では、脆弱性情報の収集、影響調査、対応判断、更新、再検査、残存リスク管理までを扱います。

既存の残存脆弱性の評価と対応方針は、[Issue #144](https://github.com/nae6/laravel-attendance-app/issues/144) を参照してください。

---

## バックアップ・復旧

アップデート前には、MySQLデータと `.env` などの必要な情報をバックアップしてください。

バックアップの取得・復元テスト・障害時の復旧方法は、[バックアップ・復旧手順書](backup-and-restore.md) を参照してください。

SQLダンプと `.env` には秘密情報が含まれるため、Gitへのコミットや第三者への共有はしないでください。

---

## テスト

テスト用データベースを作成します。

```bash
docker compose exec mysql mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS demo_test;"
```

```bash
docker compose exec php bash
php artisan test
```

以下は動作確認用のユーザーです。

### 1.ユーザー

#### 管理者

- email: admin@example.com
- password: admin1234

#### 一般ユーザー（スタッフ）

- email: testmomoko@example.com
- password: testmomoko

### 2.ログイン画面

- 管理者用ログイン画面: <http://localhost/admin/login>
- 一般ユーザー用ログイン画面: <http://localhost/login>

### 3.動作確認方法

事前に以下を実行し、初期データを登録してください。

```bash
php artisan migrate:fresh --seed
```

#### 管理者画面

1. 管理者用ログイン画面にアクセス  
   <http://localhost/admin/login>

2. 以下の管理者アカウントでログイン

   - email: admin@example.com
   - password: admin1234

3. 勤怠一覧画面で以下を確認

   - 当日のスタッフ勤怠一覧が表示される
   - 前日・翌日リンクで日付を切り替えられる
   - 各スタッフの詳細リンクから勤怠詳細画面へ遷移できる

4. 勤怠詳細画面で以下を確認

   - 出勤・退勤時刻、休憩時刻、備考を修正できる
   - 修正後、勤怠一覧画面に戻る

5. スタッフ一覧画面で以下を確認

   - 登録済みスタッフが一覧表示される
   - 詳細リンクからスタッフ別の月次勤怠一覧へ遷移できる

6. 申請一覧画面で以下を確認

   - 一般ユーザーからの修正申請が表示される
   - 詳細画面から申請内容を確認できる
   - 修正申請を承認できる

#### 会員登録・メール認証

1. 会員登録画面にアクセス  
   <http://localhost/register>

2. 名前・メールアドレス・パスワードを入力して登録

3. メール認証誘導画面の「認証はこちらから」からMailpitを開き、届いた認証メールのリンクをクリック
   <http://localhost:8025>

4. 認証完了後、勤怠登録画面に遷移する

#### スタッフ画面

1. 一般ユーザー用ログイン画面にアクセス  
   <http://localhost/login>

2. 以下の一般ユーザーアカウントでログイン

   - email: testmomoko@example.com
   - password: testmomoko

3. 勤怠登録画面で以下を確認

   - 出勤できる
   - 休憩入できる
   - 休憩戻できる
   - 退勤できる
   - 勤務状態に応じて表示されるボタンが切り替わる

4. 勤怠一覧画面で以下を確認

   - 月ごとの勤怠情報が表示される
   - 前月・翌月リンクで月を切り替えられる
   - 詳細リンクから勤怠詳細画面へ遷移できる

5. 勤怠詳細画面で以下を確認

   - 出勤・退勤時刻、休憩時刻、備考を入力して修正申請できる
   - 申請中の勤怠は承認待ちとして表示される

6. 申請一覧画面で以下を確認

   - 自分が行った修正申請が表示される
   - 承認待ち・承認済みの申請を確認できる
   - 詳細リンクから申請内容を確認できる

---

## 工夫した点

- FormRequestを用いたバリデーションの実装
- バリデーション条件にDBに保存したい値全てを含むことで、バリデーションを通ったデータのみをデータベースに保存できるようにした点（精査されてないデータを含まない仕様）
- transactionを使用した不要なデータが保存されない実装
- 複数処理のある機能にtry-catchを使用し、予期しないエラーを対策
- Controllerはリクエスト受付・画面遷移・レスポンス返却に集中させ、業務ロジックはServiceクラスへ集約（AttendanceActionService／AttendanceService／AttendanceCorrectRequestService／AdminStaffService）。処理の見通しを良くし、今後の仕様変更やテスト追加時にControllerへ複雑な処理が増えすぎないようにした
  - 「出勤記録がない」「承認済みの申請を再承認しようとした」などの業務エラーはServiceから例外（`app/Exceptions`）で通知し、画面に表示するメッセージの組み立てやログ出力はController側で行う
  - Controllerは1リソース1コントローラーとし、メソッド名もリソースコントローラーの規約（index／show／store など）に合わせた
- 勤怠ステータス（勤務外／出勤中／休憩中／退勤済）・ユーザーロール（admin／user）・申請ステータスはEnum（`app/Enums`）で管理し、文字列の直書きをなくした
- 管理者の勤怠修正では、画面から送られる日付ではなく勤怠レコード自身の日付を使って日時を組み立て、hidden inputの改ざんで別の日付に保存されないようにした
- 認証まわりはLaravel Fortifyの仕組みに寄せて実装
  - 登録・ログイン・ログアウト・メール認証後の遷移先は、Fortifyのレスポンスクラス（`app/Http/Responses`）で管理者／一般ユーザーごとに切り替え
  - メール認証のルートはFortify標準のものを使用し、独自定義による重複をなくした
- 一般ユーザーと管理者で共通の画面部品（月次勤怠一覧の表、勤怠修正フォーム、承認待ち表示）はBladeコンポーネントにまとめ、表示の差異や修正漏れが起きにくいようにした
- 権限判定もControllerから分離し、Policy/Gateへ集約
  - 管理者判定のようなモデルに依存しない全体権限はGate（`Gate::define('access-admin', ...)`）
  - 勤怠情報の所有者チェックやスタッフ閲覧可否など特定モデルに対する権限はPolicy（`AttendancePolicy` / `UserPolicy`）

---

## 苦労した点

- Carbonを用いた日時の表示
- 日時データの取り扱い
- テスト用のデータセット作成
- 実行時刻によって結果が変わるテストの対応（深夜に実行すると出勤日が前日扱いになるため、`travelTo()` でテスト内の現在時刻を固定）

## 今後の改善予定

- 勤怠の集計・レポート画面を追加
- 管理者・ユーザーのログイン画面への誘導用トップページを準備する
