# レシピ: BDR + BEAR.Async で SQL を並列化

BDR のリポジトリが宣言するのは「**何を**問い合わせるか」であって、「**いつ**」「**どう**」実行するかではありません。この最後の 1 ピースのおかげで、エンティティごとの SQL を**何の追加実装もなしに並列実行**できます。各リポジトリ呼び出しを `ResourceObject` の中に置けば、[BEAR.Async](https://github.com/bearsunday/BEAR.Async) が `#[Embed]` を経由してそれらを並列に走らせます。リポジトリの実装も SQL も変わりません。

このレシピは 2 つのライブラリの組み合わせ方を示します。どちらにも新しい抽象は持ち込みません。呼び出し側の規約が 2 つあるだけです。

## こんなときに

- 1 ページで独立した SQL を複数発行したい（user + posts + comments、ダッシュボードの各ウィジェット、検索ファセットなど）
- すでにクエリごとに SQL を書いていて、ORM に巨大な 1 文に融合されたくない
- 開発時は同期実行（PHP-FPM、デバッグしやすい）にしておき、アプリケーションの境界でだけ並列実行に切り替えたい（PHP-FPM / Apache なら ext-parallel、常駐サーバなら ext-swoole）

## 前提

```bash
composer require ray/media-query
composer require bear/async
```

[BDR パターン](./BDR_PATTERN-ja.md)（`#[DbQuery]` インターフェース・ファクトリ・不変ドメインオブジェクト）に既に親しんでいる前提です。BEAR.Async は BEAR.Sunday スタイルのリソース（`ResourceObject` + `#[Embed]`）を期待します。

## レシピ

SQL ごとに `ResourceObject` を分け、上位の集約リソースから `#[Embed]` で束ねます。

```php
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\ResourceObject;
use Ray\MediaQuery\Annotation\DbQuery;

// 1. 不変ドメインオブジェクト — リポジトリが返すスナップショット
final class UserAccount
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}

// 2. BDR リポジトリ — SQL は var/sql/user.sql に置く。
//    UserFactory が行を UserAccount にハイドレートする（詳細は BDR_PATTERN-ja.md 参照）。
interface UserRepositoryInterface
{
    #[DbQuery('user', factory: UserFactory::class)]
    public function getUser(int $id): UserAccount;
}

// 3. SQL 1 つにつき ResourceObject 1 つ — 小さく、責務を 1 つだけ持つ
class User extends ResourceObject
{
    public function __construct(private UserRepositoryInterface $repo)
    {
    }

    public function onGet(int $id): static
    {
        $this->body = ['user' => $this->repo->getUser($id)];

        return $this;
    }
}

// 4. 集約リソース — リソース間の関係を宣言するだけ。
//    3 つの Embed は AsyncLinker が自動で並列実行する。
class UserDashboard extends ResourceObject
{
    #[Embed(rel: 'user',     src: 'app://self/user{?id}')]
    #[Embed(rel: 'posts',    src: 'app://self/user/posts{?id}')]
    #[Embed(rel: 'comments', src: 'app://self/user/comments{?id}')]
    public function onGet(int $id): static
    {
        return $this;
    }
}
```

これでレシピは終わりです。ダッシュボードは並行性・スレッド・コルーチン・Promise のいずれにも触れていません。3 つのリポジトリも互いに何も知りません。

## なぜ動くか

- **BDR が SQL を宣言的に保つ**: 各 `#[DbQuery]` メソッドは 1 クエリを記述し、不変ドメインオブジェクトを返します。SQL は `var/sql/*.sql` のまま。
- **`ResourceObject` がクエリに URI を与える**: `app://self/user?id=1` は URI です。`#[Embed]` は「この集約はそのリソースを必要とする」 という**関係**を宣言します。呼び出しではありません。
- **AsyncLinker が実行戦略を選ぶ**: 実行時に 3 つの `#[Embed]` を独立したリクエストの 1 つのレベルとして見て、ext-parallel のワーカー（PHP-FPM / Apache）または Swoole のコルーチン（常駐サーバ）で並列に発行します。どちらの拡張もない場合は同じコードが逐次実行されますが、PHP-FPM は 1 リクエスト 1 プロセスなのでそれで問題ありません。

呼び出し側のコードは変わりません。「逐次」か「並列」かはアプリケーションの境界（エントリポイント・モジュール）で決まり、リポジトリやリソースの中では決めません。

## 計測

BEAR.Async は 8 個の独立した SQL バックエンドの埋め込みを持つダッシュボードを使って、コールド CLI ベンチマークとスチーディステート HTTP ベンチマーク（`wrk`）を同梱しています。数値とアダプタ選定ガイドは [BEAR.Async / docs/benchmark-results.md](https://github.com/bearsunday/BEAR.Async/blob/1.x/docs/benchmark-results.md) を参照してください。

要点は: 1 ページに独立した SQL が 2 つ以上あれば、このレシピで埋め込み数ぶんだけページが速くなります（プールやランタイムのオーバーヘッドを差し引いて）。リポジトリのコードは一切変わりません。

## Swoole での注意

ext-swoole 上で動かす場合、コルーチンはプロセスメモリを共有するため、`PDO` 接続をコルーチン境界をまたいで共有してはいけません。BEAR.Async の `PdoPoolModule` をインストールして、各コルーチンがプールから `PDO` を借りるようにします。接続取得は遅延され、`#[Embed]` を宣言した時点ではプールには触れず、各リクエストが実際に実行されるときに取得します。

詳細は [BEAR.Async README — Swoole execution](https://github.com/bearsunday/BEAR.Async#swoole-execution-ext-swoole) を参照してください。

## 関連

- [BDR パターン解説](./BDR_PATTERN-ja.md) — リポジトリ + ファクトリ + ドメインオブジェクトの土台
- [BEAR.Async](https://github.com/bearsunday/BEAR.Async) — BEAR.Sunday 向け `#[Embed]` 並列実行ライブラリ
- [BEAR.Async ベンチマーク結果](https://github.com/bearsunday/BEAR.Async/blob/1.x/docs/benchmark-results.md) — コールド CLI と スチーディステート HTTP の実測値
- [並列リソース実行マニュアル (BEAR.Sunday ドキュメント)](https://bearsunday.github.io/manuals/1.0/ja/async.html)
