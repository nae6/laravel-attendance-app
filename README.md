# 勤怠管理アプリ

Laravelで作成した勤怠管理アプリです。
一般ユーザーと管理者で権限を分離し、基本的な機能を備えたシンプルなアプリです。

---

## 概要

- 一般ユーザーにはユーザー登録・ログイン・メール認証機能があります
- 一般ユーザーは勤怠の登録ができます
- 一般ユーザーは自身の勤怠一覧とその詳細を見ることができます
- 一般ユーザーは勤怠の修正を申請できます
- 管理者はスタッフの一覧が確認できます
- 管理者はスタッフ別の勤怠一覧を一覧を確認できます
- 管理者は勤怠の修正を申請できます
- 管理者は勤怠の修正申請を承認/否認できます

---

## ER図

### テーブル設計

- users
- attendances
- attendances_correct_requests
- breaks

---

![ER Diagram](ER図を挿入する)

---

## 環境構築

### 1. リポジトリをクローン

```bash
git clone <URL>
cd attendance-management-app
```

### 2. laravel・Dockerセットアップ

1. Dockerを起動する

2. プロジェクト直下で以下のコマンドを実行する

```bash
make init
```

※このコマンドには.envの作成も含まれていますので、初回時のみ行ってください

### 3. .envの環境変数を設定

DB、MAILの設定を変更してください

設定後はキャッシュをクリアしてください

```bash
php artisan config:clear
```

---

## テストユーザー

動作確認用のユーザーです。

### 管理者

- email: 
- password: 

---

### 一般ユーザー

- 
- 

---

## 使用技術

- PHP: 8.4.17
- Laravel: 12.12.2
- DB: MySQL
- MySQL: 8.0
- nginx: 1.28.1
- View: Blade
- Docker / Docker Compose
