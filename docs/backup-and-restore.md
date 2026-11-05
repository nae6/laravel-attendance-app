# Backup and Restore Guide

## 1. 概要

本ドキュメントは、勤怠管理アプリケーション（attendance-management）のバックアップおよび復旧手順を定義する。

PHP、Laravel、MySQL、Dockerなどのアップデート時に問題が発生した場合でも、更新前の状態へ復旧できるようにすることを目的とする。

## 2. 対象環境

| 項目 | 更新前の環境 |
|---|---|
| アプリケーション | Laravel 12.62.0 |
| PHP | 8.4.23 |
| MySQL | 8.0.26 |
| Webサーバー | Nginx 1.28.1 |
| コンテナ管理 | Docker Compose |
| データベース名 | attendance_db |
| データ保存先 | Docker named volume: db-data |

※ 上記はIssue 2実施時点の環境。今後の更新時には対象バージョンを確認すること。

## 3. バックアップ対象

| 対象 | 保存方法 |
|---|---|
| ソースコード | Git |
| MySQLデータベース | mysqldump |
| Laravel環境設定 | .envのコピー |
| Docker構成 | Git管理されたCompose・Dockerfile等 |

MySQLのSQLダンプは、データベースの論理バックアップである。

Dockerボリューム全体の物理バックアップではなく、MySQLのユーザー権限やサーバー設定まで完全に復元するものではない。

## 4. バックアップ手順

すべてのコマンドは `attendance-management` ディレクトリで実行する。

### 4.1 Gitの状態確認

```bash
git status
git branch --show-current
git log -1 --oneline
```

更新前のコミットIDを記録しておく。

未コミットの変更がある場合は、適切にコミットするか、別途保護してから更新作業を開始する。

### 4.2 バックアップ先の作成

```bash
mkdir -p ../attendance-management-backups

chmod 700 ../attendance-management-backups

BACKUP_DIR="$(cd ../attendance-management-backups && pwd)"
```

バックアップはGitリポジトリの外に保存する。

### 4.3 MySQLのバックアップ

```bash
BACKUP_FILE="$BACKUP_DIR/attendance_db_$(date +%Y%m%d_%H%M%S).sql"

docker compose exec -T mysql sh -c '
  export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"
  exec mysqldump \
    -u root \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    --no-tablespaces \
    attendance_db
' > "$BACKUP_FILE"
```

正常終了を確認する。

```bash
echo $?
```

`0` であれば正常終了。

ファイルを確認する。

```bash
ls -lh "$BACKUP_FILE"
test -s "$BACKUP_FILE"
tail -n 5 "$BACKUP_FILE"
```

注意事項：

- InnoDBなどのトランザクション対応テーブルを想定する。
- バックアップ中のDDL変更は避ける。
- エラー時に作成された不完全なSQLファイルを使用しない。
- バックアップ取得後の更新データは、このSQLファイルには含まれない。

### 4.4 Laravelの.envをバックアップ

```bash
cp -p src/.env "$BACKUP_DIR/laravel.env.backup"

chmod 600 "$BACKUP_DIR/laravel.env.backup"
```

`.env` には認証情報や `APP_KEY` などの秘密情報が含まれる。

GitHubへのコミットや第三者への共有は禁止する。

## 5. 復元テスト

既存の `attendance_db` を上書きしないため、独立したテスト用MySQLコンテナを使用する。

### 5.1 バックアップファイルの指定

```bash
BACKUP_DIR="$(cd ../attendance-management-backups && pwd)"
ls -lh "$BACKUP_DIR"
```

復元対象のファイルを指定する。

```bash
BACKUP_FILE="$BACKUP_DIR/<実際のバックアップファイル名>.sql"
```

### 5.2 テスト用MySQLコンテナの起動

```bash
docker run -d \
  --name attendance-mysql-restore-test \
  --network none \
  -e MYSQL_ROOT_PASSWORD=restore_test_password \
  -e MYSQL_DATABASE=restore_test_db \
  mysql:8.0.26
```

既存のDockerボリューム `db-data` は使用しない。

### 5.3 MySQLの起動確認

```bash
docker exec attendance-mysql-restore-test \
  sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "SELECT 1;"'
```

MySQLが初期化中の場合は、起動完了後に再実行する。

### 5.4 SQLファイルのインポート

```bash
docker exec -i attendance-mysql-restore-test \
  sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysql -uroot restore_test_db' \
  < "$BACKUP_FILE"
```

実行直後に終了コードを確認する。

```bash
echo $?
```

`0` であればインポート処理は正常終了。

この手順は、データベース名を固定する `CREATE DATABASE` や `USE` 文を含まないSQLダンプを想定している。

### 5.5 テーブルの確認

復元先：

```bash
docker exec attendance-mysql-restore-test \
  sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "SHOW TABLES FROM restore_test_db;"'
```

元のデータベース：

```bash
docker compose exec mysql \
  sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "SHOW TABLES FROM attendance_db;"'
```

両方のテーブル一覧を比較する。

### 5.6 データの整合性確認

例：usersテーブルの件数を比較する。

元のデータベース：

```bash
docker compose exec mysql \
  sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot attendance_db -e "SELECT COUNT(*) AS total FROM users;"'
```

復元先：

```bash
docker exec attendance-mysql-restore-test \
  sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot restore_test_db -e "SELECT COUNT(*) AS total FROM users;"'
```

その他の重要なテーブルも同様に確認する。

比較対象はバックアップ取得時点のデータとする。

件数の一致だけでは完全な整合性を保証できないため、必要に応じて主要データの内容やテーブル定義も検証する。

### 5.7 テスト用コンテナの削除

復元テストが成功し、確認結果を記録した後に実行する。

```bash
docker rm -f -v attendance-mysql-restore-test
```

削除を確認する。

```bash
docker ps -a --filter name=attendance-mysql-restore-test
```

## 6. アップデート失敗時の復旧方針

### 6.1 ソースコードの復旧

更新前に記録したGitコミットへ戻す。

作業ブランチを残したまま、更新前のコミットから復旧用ブランチを作成できる。

```bash
git switch -c recovery/<任意のブランチ名> <更新前のコミットID>
```

必要に応じて、更新前のDockerfileやCompose設定でコンテナを再構築する。

### 6.2 MySQLの復旧

MySQLのアップグレード後に障害が発生した場合、更新済みのデータディレクトリを旧バージョンでそのまま再利用しない。

復旧時の基本方針：

1. アプリケーションの書き込みを停止する
2. 更新前のMySQLバージョンを確認する
3. 元のデータボリュームを保全する
4. 更新前バージョンのMySQLを新しいデータ領域で起動する
5. 更新前に取得したSQLダンプを復元する
6. 必要なユーザー・権限・接続設定を再設定する
7. テーブル・データ・アプリケーションの動作を確認する
8. 正常動作を確認してからサービスを再開する

本番相当のデータに対して、復元先を確認せずにインポートしたり、既存ボリュームを削除したりしないこと。

実際の復旧コマンドは、障害発生時のMySQLバージョン、Compose構成、ボリューム状態を確認して決定する。

### 6.3 Laravel環境設定の復旧

保存した `.env` を復旧先の環境へ戻す。

特に `APP_KEY`、データベース接続設定、メール設定などを確認する。

既存の `.env` が存在する場合は、上書き前に保全する。

### 6.4 動作確認

- Laravelが正常に起動する
- データベースに接続できる
- ログイン・ログアウトが正常に動作する
- 勤怠データを表示できる
- 勤怠の登録・更新など主要機能が動作する
- Laravelのログに重大なエラーがない
- 自動テストが成功する

## 7. セキュリティ上の注意事項

- SQLダンプと.envはGitに登録しない
- バックアップファイルのアクセス権限を制限する
- 秘密情報をログやIssueに貼り付けない
- バックアップの保管期間を決める
- 必要に応じて暗号化した別ストレージへコピーする
- 復元テストには可能な限り隔離された環境を使用する

## 8. 復旧手順の制約

この手順書では、SQLダンプの取得と別MySQL環境への復元を検証対象とする。

以下は別途検証が必要：

- Dockerボリューム全体の物理バックアップと復旧
- MySQLサーバー全体のユーザー・権限・設定の復元
- MySQL 8.4から8.0への実際のロールバック
- アプリケーションを含む環境全体の復旧訓練

## 9. 参考資料

- [MySQL 8.0 Reference Manual - mysqldump](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html)
- [MySQL 8.0 Reference Manual - Reloading SQL-Format Backups](https://dev.mysql.com/doc/refman/8.0/en/reloading-sql-format-dumps.html)
- [Docker CLI - docker run](https://docs.docker.com/reference/cli/docker/container/run/)
- [Docker CLI - docker exec](https://docs.docker.com/reference/cli/docker/container/exec/)
- [Laravel 12 - Configuration](https://laravel.com/docs/12.x/configuration)