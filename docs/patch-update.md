# セキュリティ更新手順書（パッチアップデート編）

## 1. 目的

本手順書は、勤怠管理アプリケーションで使用しているソフトウェアのパッチアップデートを、安全かつ効率的に実施することを目的とする。

更新範囲を必要最小限に限定し、既存機能への影響を抑えながら脆弱性や不具合を解消する。

## 2. 適用範囲

以下のような、同一メジャー・マイナーバージョン内のパッチアップデートを対象とする。

- PHP本体およびPHP拡張
- Laravelフレームワーク
- Composer依存パッケージ
- Dockerイメージ

メジャー・マイナーバージョンの変更、詳細な脆弱性調査、互換性に問題がある場合は、[セキュリティ更新手順書](security-update.md) を参照する。

### 基本方針

- 更新対象を必要最小限に限定する。
- 更新対象外のソフトウェアは意図的に更新しない。
- 不要な依存パッケージの更新は行わない。
- 更新前後のバージョンと検証結果を記録する。
- 問題発生時に更新前の状態へ戻せるようにする。

## 3. 更新内容の確認

### 3.1 公式情報の確認

更新対象の公式リリースノートを確認する。

| 対象 | 参照先 |
|---|---|
| PHP | [PHP ChangeLog](https://www.php.net/ChangeLog-8.php) |
| Laravel | [Laravel Releases](https://github.com/laravel/framework/releases) |
| Composer依存パッケージ | 各パッケージの公式リポジトリ |
| Dockerイメージ | [Docker Hub](https://hub.docker.com/) |

以下の項目を確認する。

- 更新先のバージョン
- セキュリティ修正・不具合修正の内容
- アプリケーションに関係する変更
- 互換性に影響する変更の有無

### 3.2 GitHub Issueの作成

以下の内容を記載したIssueを作成する。

- 更新対象と更新前後のバージョン
- 更新理由
- 公式リリースノートへのリンク
- 作業内容
- 完了条件

## 4. 更新前の準備

以下のコマンドは、リポジトリのルートディレクトリで実行する。

### 4.1 作業ブランチの作成

作業ツリーに未コミットの変更がないことを確認する。

```bash
git status
git switch main
git pull --ff-only origin main
git switch -c fix/154-php-patch-update
```

ブランチ名のIssue番号と更新対象は、実際の作業内容に合わせて変更する。

### 4.2 現在の環境確認

更新対象に応じて必要なコマンドを実行する。

```bash
# コンテナの起動状況
docker compose ps

# PHPバージョン
docker compose exec php php -v

# PHP拡張
docker compose exec php php -m

# Laravelバージョン
docker compose exec php php artisan --version

# Composerバージョン
docker compose exec php composer --version

# Dockerイメージ
docker compose config --images
```

更新前のバージョンをIssueに記録する。

### 4.3 更新前の脆弱性検査

更新対象に応じて実施する。

PHP・Dockerイメージの場合：

```bash
docker scout quickview local://attendance-management-php

docker scout cves local://attendance-management-php --only-severity critical,high
```

Laravel・Composer依存パッケージの場合：

```bash
docker compose exec php composer audit
```

検査結果を記録し、更新後の結果と比較できるようにする。

### 4.4 更新前のテスト

```bash
docker compose exec php php artisan test
```

すべてのテストが成功することを確認する。

更新前からテストが失敗している場合は、原因を確認してから更新作業に進む。

### 4.5 バックアップ・復旧方法の確認

更新対象に応じて必要なバックアップを取得する。

PHP・Composer依存パッケージのみの更新で、データベースや設定ファイルを変更しない場合は、原則として以下を確認する。

- 更新前のGitコミット
- 更新前のDockerイメージ
- `composer.json` と `composer.lock` の状態

Dockerイメージを更新する場合は、更新前のイメージIDを確認する。

```bash
docker compose images php
```

必要に応じて旧イメージに退避用タグを付ける。

```bash
docker image tag <IMAGE_ID> attendance-management-php:rollback
```

データベースや永続データに影響する更新を行う場合は、[バックアップ・復旧手順書](backup-and-restore.md) に従う。

## 5. 更新作業

更新対象に該当する手順のみを実施する。

### 5.1 PHPの更新

Dockerfileのベースイメージを確認する。

パッチバージョンを固定している場合は、更新先のバージョンに変更する。

例：

```dockerfile
# 更新前
FROM php:8.5.12-fpm

# 更新後
FROM php:8.5.13-fpm
```

`php:8.5-fpm` のようにマイナーバージョンまで指定している場合は、最新のパッチバージョンを含むイメージが取得されるため、Dockerfileの変更は必須ではない。

PHPサービスのみを再ビルドする。

```bash
docker compose build --pull php
```

PHPコンテナのみを再作成する。

```bash
docker compose up -d --no-deps --force-recreate php
```

PHPのバージョンを確認する。

```bash
docker compose exec php php -v
```

意図したバージョンになっていることを確認する。

**注意事項：**

- MySQL・Nginxなど、更新対象外のサービスは再ビルドしない。
- PHPのみの更新では `composer update` を実行しない。
- PHPイメージの再ビルドに伴い、OSパッケージ、PHP拡張、Composerなどが更新される可能性があるため、更新前後の状態を確認する。
- `docker compose down -v` は実行しない。

### 5.2 Laravel・Composer依存パッケージの更新

対象パッケージの現在のバージョンを確認する。

```bash
docker compose exec php composer show --locked <package-name>
```

更新内容を事前確認する。

```bash
docker compose exec php composer update <package-name> --dry-run
```

更新対象と依存パッケージの変更範囲に問題がなければ、更新を実施する。

```bash
docker compose exec php composer update <package-name>
```

更新後の差分を確認する。

```bash
git diff -- src/composer.json src/composer.lock
```

意図したパッチバージョンを超える更新や、不要な依存パッケージの更新が発生していないことを確認する。

依存関係の制約によって更新できない場合は、既存のセキュリティ更新手順書に従って調査する。

### 5.3 Dockerイメージの更新

更新対象サービスのDockerfileまたはCompose設定を確認し、必要に応じてイメージタグを変更する。

Dockerfileからビルドするサービスの場合：

```bash
docker compose build --pull <service-name>
```

Composeでイメージを直接指定するサービスの場合：

```bash
docker compose pull <service-name>
```

対象コンテナを再作成する。

```bash
docker compose up -d --no-deps --force-recreate <service-name>
```

MySQLなど、永続データに影響する可能性があるサービスについては、公式の更新手順とバックアップ・復旧手順を事前に確認する。

## 6. 更新後の検証

### 6.1 実行環境の確認

```bash
docker compose ps
docker compose exec php php -v
docker compose exec php php -m
docker compose exec php php artisan --version
docker compose exec php composer --version
docker compose exec php composer check-platform-reqs
```

以下を確認する。

- 必要なコンテナが起動している。
- 更新対象が意図したバージョンになっている。
- 必要なPHP拡張が存在する。
- Composerのプラットフォーム要件を満たしている。
- 更新対象外に意図しない変更が発生していない。

### 6.2 脆弱性の再検査

更新前に実施した検査を再実行する。

PHP・Dockerイメージの場合：

```bash
docker scout quickview local://attendance-management-php

docker scout cves local://attendance-management-php --only-severity critical,high
```

Laravel・Composer依存パッケージの場合：

```bash
docker compose exec php composer audit
```

更新前後の結果を比較し、対象の脆弱性が解消されていることを確認する。

検査ツールで確認できない脆弱性については、実際の稼働バージョンと公式の修正版情報を照合する。

残存脆弱性については、[Issue #144](https://github.com/nae6/laravel-attendance-app/issues/144) および既存のセキュリティ更新手順書に従って管理する。

### 6.3 自動テスト

```bash
docker compose exec php php artisan test
```

すべてのテストが成功することを確認する。

### 6.4 動作確認

更新内容に関係する機能を中心に、ブラウザから動作確認する。

- ログイン・ログアウト
- 出勤・退勤
- 休憩開始・終了
- 勤怠情報の表示・更新

必要に応じてログを確認する。

```bash
docker compose logs --tail=100 php
```

### 6.5 変更差分の確認

```bash
git status
git diff
```

意図しないファイルの変更がないことを確認する。

PHPのみの更新の場合は、`composer.json` と `composer.lock` が変更されていないことを確認する。

## 7. 問題発生時の対応

更新後に問題が発生した場合は、原因を調査し、必要に応じて更新前の状態へ戻す。

### PHP・Dockerイメージの場合

- 更新前のDockerfile・Compose設定を復元する。
- 退避した旧イメージを使用して対象コンテナを再作成する。
- バージョンと動作を再確認する。

### Composer依存パッケージの場合

- 更新前の `composer.json` と `composer.lock` を復元する。
- 以下のコマンドで依存パッケージを復元する。

```bash
docker compose exec php composer install
```

共有済みのコミットを取り消す場合は、原則として `git revert` を使用する。

詳細な復旧方法は、[バックアップ・復旧手順書](backup-and-restore.md) を参照する。

ロールバックによって脆弱性が再び存在する状態になる場合は、未解消のリスクとして記録し、再対応を検討する。

## 8. 更新結果の記録

GitHub IssueまたはPull Requestに以下を記録する。

- 更新対象
- 更新前後のバージョン
- 脆弱性検査の結果
- 自動テストの結果
- 動作確認の結果
- 更新対象外への影響
- 残存する脆弱性（該当する場合）

変更ファイルが存在する場合は、コミット・Pushを行い、Pull Requestを作成する。

イメージの再ビルドのみでGit管理対象ファイルに変更がない場合は、不要なコミットを作成せず、Issueに更新結果を記録する。

## 9. 完了条件（Definition of Done）

- [ ] 公式リリースノートを確認した
- [ ] 更新前のバージョンとテスト結果を記録した
- [ ] 必要なバックアップ・復旧方法を確認した
- [ ] 更新対象が意図したバージョンになっている
- [ ] 更新対象外に意図しない変更がない
- [ ] 対象の脆弱性の修正状況を確認した
- [ ] Composerのプラットフォーム要件を満たしている
- [ ] Laravelの自動テストがすべて成功した
- [ ] 更新内容に関係する機能の動作確認が完了した
- [ ] GitHub Issueに更新結果を記録した
- [ ] 変更ファイルがある場合はPull Requestを作成・マージした
- [ ] GitHub Issueをクローズした

## 10. 関連ドキュメント

- [README](README.md)
- [セキュリティ更新手順書](security-update.md)
- [バックアップ・復旧手順書](backup-and-restore.md)
- [Issue #144：残存脆弱性の評価・対応方針](https://github.com/nae6/laravel-attendance-app/issues/144)
- [PHP ChangeLog](https://www.php.net/ChangeLog-8.php)
- [Composer CLI](https://getcomposer.org/doc/03-cli.md)
- [Docker Compose CLI](https://docs.docker.com/reference/cli/docker/compose/)
- [Docker Scout](https://docs.docker.com/scout/)