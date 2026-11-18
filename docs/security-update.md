# セキュリティ更新手順書

## 1. 目的

本手順書は、勤怠管理アプリケーションで使用しているソフトウェアに脆弱性が発見された際、影響調査・対応判断・更新・検証・記録を安全かつ再現可能な方法で実施することを目的とする。

更新作業では、単に最新バージョンへ変更するのではなく、脆弱性の影響範囲、修正版の有無、互換性、アプリケーションへの影響を確認してから対応を決定する。

## 2. 対象範囲

本手順書は、以下のソフトウェアを対象とする。

- PHP本体およびPHP拡張
- Laravelフレームワーク
- SymfonyなどのComposer依存パッケージ
- Dockerイメージ（PHP・MySQL・Nginx・Mailpit）

具体的な環境構築・更新コマンドは [README](README.md)、バックアップ・復旧方法は [バックアップ・復旧手順書](backup-and-restore.md) を参照する。

既知の残存脆弱性については、[GitHub Issue #144](https://github.com/nae6/laravel-attendance-app/issues/144) に記録された評価・対応方針を参照する。

## 3. 公式セキュリティ情報の確認

### 3.1 情報源

脆弱性情報は、原則としてソフトウェアの公式ドキュメントやセキュリティアドバイザリから確認する。

| 対象 | 主な情報源 |
|---|---|
| PHP | [PHP Supported Versions](https://www.php.net/supported-versions.php)、[PHP ChangeLog](https://www.php.net/ChangeLog-8.php) |
| Laravel | [Laravel公式ドキュメント](https://laravel.com/docs)、[Laravel Security Advisories](https://github.com/laravel/framework/security/advisories) |
| Symfony | [Symfony Security Advisories](https://symfony.com/blog/category/security-advisories) |
| Composer依存パッケージ | [Composer audit](https://getcomposer.org/doc/03-cli.md#audit) |
| Dockerイメージ | [Docker Scout](https://docs.docker.com/scout/) |
| Debianパッケージ | [Debian Security Tracker](https://security-tracker.debian.org/tracker/) |

### 3.2 脆弱性を調査する際の確認項目

脆弱性を発見した場合は、以下を確認する。

1. **CVE番号**：どの脆弱性を調査しているか。
2. **影響を受ける製品・バージョン**：現在使用しているバージョンが対象か。
3. **脆弱性の内容**：どのような攻撃が成立する可能性があるか。
4. **攻撃成立条件**：脆弱な機能を使用しているか、外部から到達可能か。
5. **修正版の有無**：公式に修正版が公開されているか。
6. **対応の緊急度**：重大度、悪用状況、実際の影響範囲から判断する。

調査結果はGitHub Issueに記録し、根拠となる公式情報へのリンクを残す。

### 3.3 PHPの脆弱性を調査する例

PHPに脆弱性が発表された場合は、以下の順序で確認する。

1. PHP公式サイトで脆弱性情報を確認する。
2. 影響を受けるPHPバージョンと修正版を確認する。
3. Dockerコンテナで実際に使用しているPHPバージョンを確認する。
4. 現在のバージョンが影響対象であれば、修正方法と互換性を調査する。

実行環境の確認コマンド：

```bash
docker compose exec php php -v
```

**注意：** PHPのメジャー・マイナーバージョンを上げることと、セキュリティ修正版へ更新することは別の判断である。脆弱性の修正だけが目的であれば、互換性への影響が小さいパッチ更新を優先して検討する。

## 4. 影響調査

脆弱性情報を確認したら、実際のアプリケーションが影響を受けるか調査する。

### 4.1 使用バージョンの確認

リポジトリのルートディレクトリで実行する。

PHPとLaravelのバージョンを確認する。

```bash
docker compose exec php php -v
docker compose exec php php artisan --version
```

Composerの依存パッケージを確認する。

```bash
docker compose exec php composer show --locked
```

特定のパッケージを調べる場合は、パッケージ名を指定する。

```bash
docker compose exec php composer show --locked symfony/http-foundation
```

Dockerイメージを確認する。

```bash
docker compose config --images
```

### 4.2 脆弱性検査

#### Composer依存パッケージ

```bash
docker compose exec php composer audit
```

既知のセキュリティアドバイザリを確認する。

#### Dockerイメージ

まず、Composeで使用するイメージ名を確認する。

```bash
docker compose config --images
```

Docker Scoutで、各イメージを検査する。

```bash
docker scout quickview local://attendance-management-php
docker scout quickview local://attendance-management-mysql
docker scout quickview local://attendance-management-nginx
docker scout quickview axllent/mailpit:v1.33.1
```

Critical・Highの詳細と修正版の有無を確認する。

```bash
docker scout cves local://attendance-management-php --only-severity critical,high
```

上記は現在のプロジェクト構成に基づく例である。イメージ名やタグが変更された場合は、実際の構成に合わせて指定する。

検査結果では、脆弱性の件数だけでなく、対象パッケージ、CVE番号、修正版の有無を確認する。

※ 検査と更新は別の作業である。`docker compose build --pull` はイメージの再ビルドを行うため、影響調査だけが目的の場合は実行しない。

### 4.3 調査結果の記録

GitHub Issueに以下を記録する。

- CVE番号またはアドバイザリ番号
- 対象パッケージと現在のバージョン
- 影響を受けるバージョンの範囲
- 修正版の有無
- アプリケーションでの使用状況
- 攻撃成立条件と想定される影響
- 調査に使用した公式情報
- 対応方針とその理由

## 5. 対応方針の決定

影響調査の結果に基づいて、以下のいずれかを選択する。

### 5.1 修正する

修正版が提供されており、アプリケーションに適用できる場合は、更新を計画する。

- 修正版の変更内容を確認する。
- 依存関係と互換性を確認する。
- 必要なバックアップを取得する。
- 更新後に監査と自動テストを実施する。

### 5.2 追加調査する

影響範囲や修正版の適用可否が不明な場合は、調査を継続する。

不明な点と次の調査項目をGitHub Issueに記録する。

### 5.3 残存リスクとして管理する

修正版が提供されていない場合や、直ちに更新できない場合は、リスクを評価する。

- 脆弱な機能を使用しているか確認する。
- 外部から攻撃可能か確認する。
- 一時的な緩和策を検討する。
- 対応を保留する理由と再確認の条件を記録する。

既存の残存脆弱性は [Issue #144](https://github.com/nae6/laravel-attendance-app/issues/144) の評価・対応方針を参照する。

READMEに記載された週次確認の運用に従い、修正版が公開された場合は対応を再検討する。

**残存リスクとして受容した脆弱性を、修正済みとして扱ってはならない。**

残存脆弱性は週次で修正版の提供状況を確認する。

新たに対応が必要となった脆弱性は、`security-watch` ラベルを付けたGitHub Issueで追跡する。

修正版が公開された場合は、影響と互換性を再評価し、個別の更新作業として対応する。

### 5.4 優先順位の判断

CVSSなどの重大度だけでなく、以下を総合して優先順位を決める。

- 実際に悪用されているか
- 外部から到達可能か
- 攻撃成立に認証や特別な条件が必要か
- アプリケーションが該当機能を使用しているか
- 情報漏えいやサービス停止などの影響
- 修正版・緩和策の有無

緊急度が高いと判断した場合は、通常の更新予定を待たずに対応する。

## 6. セキュリティ更新の実施

脆弱性の影響調査が完了し、修正する方針を決定した場合は、以下の順序で更新する。

### 6.1 GitHub Issueと作業ブランチ

更新作業を開始する前にGitHub Issueを作成する。

Issueには、対象の脆弱性、影響範囲、対応方針、作業内容、完了条件を記載する。

作業ブランチはIssue番号と作業内容が分かる名前にする。

例：

```bash
git switch main
git pull --ff-only origin main
git switch -c fix/154-security-update
```

※ `154` は例示用のIssue番号であり、実際の作業では対象Issueの番号を使用する。すでに作業ブランチがある場合は新規作成しない。

### 6.2 バックアップ

更新前に、必要なデータをバックアップする。

- MySQLデータ
- `.env`
- 更新前のGitコミットと依存関係の状態

バックアップの取得・復旧方法は [バックアップ・復旧手順書](backup-and-restore.md) を参照する。

バックアップが存在することに加え、復旧できることを確認する。

### 6.3 互換性の確認

更新前に、公式のリリースノートとアップグレードガイドを確認する。

特に以下を確認する。

- PHPの必要バージョン
- LaravelとComposer依存パッケージの互換性
- PHP拡張の対応状況
- Dockerベースイメージの変更内容
- 設定ファイルやAPIの破壊的変更

Composerの依存関係に問題がある場合は、更新を強行せず、制約の原因を調査する。
Composer依存パッケージの更新可能状況は、以下で確認できる。

```bash
docker compose exec php composer outdated --direct
```

依存関係の制約によって更新できない場合は、`composer prohibits` などを使用して原因を調査する。

メジャーバージョンを変更する場合は、`composer.json` の制約と公式アップグレードガイドを確認する。

### 6.4 更新作業

原則として、脆弱性の修正に必要な範囲で更新する。

#### Composer依存パッケージ

リポジトリのルートディレクトリで実行する。

`composer.json` の制約範囲内で全体を更新する場合：

```bash
docker compose exec php composer update
```

特定のパッケージを更新する場合：

```bash
docker compose exec php composer update laravel/framework --with-all-dependencies
```

`--with-all-dependencies` は、指定したパッケージの更新に必要な依存パッケージについても、許可される範囲で更新するためのオプションである。

実際の更新対象に応じて、パッケージ名を変更する。

更新後は `composer.json` と `composer.lock` の差分を確認する。

**プロジェクト固有の注意事項：**

PHPコンテナにはGitをインストールしていない。Composerは通常dist形式でパッケージを取得するが、VCSソースからの取得が必要な依存関係ではGitが必要になる場合がある。

その場合は、取得方法や実行環境を確認して対応する。

#### Dockerイメージ

DockerfileとCompose設定を確認し、必要なイメージを更新する。

```bash
docker compose build --pull
docker compose up -d
```

`docker compose build --pull` はビルド時に使用するベースイメージの取得を試みる。Composeで直接指定しているイメージの更新まで一括して保証するものではない。

`docker compose up -d` によってコンテナが再作成される場合があるため、データとサービスへの影響を確認してから実行する。

#### 更新後の確認

実際の稼働バージョンを確認し、続く「7. 更新後の検証」を実施する。

## 7. 更新後の検証

更新作業が完了したら、脆弱性の修正状況とアプリケーションの動作を検証する。

### 7.1 実行環境の確認

```bash
docker compose ps
docker compose exec php php -v
docker compose exec php php artisan --version
docker compose exec php composer check-platform-reqs
```

確認項目：

- 必要なコンテナが起動している
- PHP・Laravelが意図したバージョンになっている
- Composerのプラットフォーム要件を満たしている

### 7.2 脆弱性の再検査

```bash
docker compose exec php composer audit
```

Docker Scoutによる検査方法は [README](README.md) を参照する。

更新前後の検査結果を比較し、対象の脆弱性が解消されたか確認する。

`composer audit` の結果が0件でも、PHP本体やOSパッケージに脆弱性が存在しないことを意味するわけではない。

### 7.3 Laravelの自動テスト

```bash
docker compose exec php php artisan test
```

すべてのテストが成功することを確認する。

テストに失敗した場合は、原因を調査し、更新による影響かどうかを確認する。

### 7.4 検証結果の記録

GitHub IssueまたはPull Requestに以下を記録する。

- 更新前後のバージョン
- 更新したパッケージ・Dockerイメージ
- Composer監査の結果
- Docker Scoutの結果
- Laravelテストの結果
- 残存する脆弱性と対応方針

## 8. 問題発生時の対応・ロールバック

更新後に問題が発生した場合は、影響範囲を確認し、必要に応じて変更を取り消す。

詳細な復旧方法は、[バックアップ・復旧手順書](backup-and-restore.md) を参照する。

### 8.1 ソースコード・依存パッケージ

- 更新前のGitコミットを確認する。
- 更新前の `composer.json` と `composer.lock` を復元する。
- 必要に応じて `composer install` で依存パッケージを再インストールする。
- 共有済みコミットを取り消す場合は、原則として `git revert` を使用する。

### 8.2 Docker・データベース

- 更新前のDockerfileとCompose設定を確認する。
- 必要な旧イメージが利用できるか確認する。
- MySQLのデータディレクトリを旧バージョンで安易に再利用しない。
- データベースの復旧は専用手順書に従う。

**注意：** データベースのスキーマ変更を伴う場合、コードを戻すだけでは復旧できないことがある。復旧前にデータの整合性を確認する。

## 9. 完了条件（Definition of Done）

セキュリティ更新作業は、以下を満たした場合に完了とする。

- [ ] 対象の脆弱性と影響範囲を調査した
- [ ] 対応方針と判断理由をGitHub Issueに記録した
- [ ] 必要なバックアップと復旧方法を確認した
- [ ] 更新後の実行環境と依存関係を確認した
- [ ] Composer監査とDocker Scoutの結果を確認した
- [ ] Composer更新時は `composer.json` と `composer.lock` の変更内容を確認した
- [ ] Laravel自動テストが成功した
- [ ] 対象の脆弱性の修正状況を確認した
- [ ] 未解消の脆弱性は理由・緩和策・再確認方針を記録した
- [ ] Pull Requestで変更内容と検証結果を確認した
- [ ] Pull Requestをマージし、GitHub Issueをクローズした

残存脆弱性がある場合でも、影響評価と対応方針が適切に記録され、受容判断がなされていれば、当該更新作業を完了できる。ただし、未解消の脆弱性を修正済みとは扱わない。

## 10. 関連ドキュメント

- [README：環境構築・パッケージ更新・セキュリティ検査](README.md)
- [バックアップ・復旧手順書](backup-and-restore.md)
- [Issue #144：残存脆弱性の評価・対応方針](https://github.com/nae6/laravel-attendance-app/issues/144)
- [PHP公式ドキュメント](https://www.php.net/)
- [Composer公式ドキュメント](https://getcomposer.org/doc/)
- [Laravel公式ドキュメント](https://laravel.com/docs)
- [Docker Scout公式ドキュメント](https://docs.docker.com/scout/)