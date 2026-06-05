---
layout: default
title: Ray.MediaQuery FAQ
description: Ray.MediaQuery、interface-driven SQL、戻り値、factory、BDR、アーキテクチャに関する FAQ。
lang: ja
permalink: /faq/ja/
---

# Ray.MediaQuery FAQ

[English]({{ '/faq/' | relative_url }})

## プロダクト

### Q: Ray.MediaQuery とは何ですか？

**A: Ray.MediaQuery は Interface-Driven SQL for PHP です。** PHP インターフェイスを定義し、`#[DbQuery]` を付け、SQL ファイルを書くと、Ray.MediaQuery が Ray.Di と AOP によって実装を提供します。

インターフェイスが契約です。SQL ファイルがクエリです。戻り値の型が、結果をどう fetch、hydrate、wrap、または無視するかを宣言します。

### Q: ORM と何が違いますか？

**A: Ray.MediaQuery は SQL をオブジェクト抽象の裏に隠さず、見えるままにします。** ORM は、データベースの形とオブジェクトモデルがそろっているときに有効です。一方、JOIN、CTE、window function、集約、計算列、画面固有の projection が必要になると、ORM のモデルでは困難になりがちです。

Ray.MediaQuery では、その仕事を SQL に直接任せ、結果に PHP の型を与えます。

### Q: なぜインターフェイスを使うのですか？

**A: 実装クラスを書かずに、クエリへ安定した PHP の境界を与えるためです。** 呼び出し側はメソッドシグネチャと戻り値型に依存します。Ray.MediaQuery は、そのメソッドを実行時に SQL へ結び付けます。

query builder chain も、生成された repository class も、隠れた SQL もありません。

### Q: Ray.MediaQuery は読み取り専用ですか？

**A: いいえ。** `SELECT`、`INSERT`、`UPDATE`、`DELETE`、multi-statement SQL file を実行できます。

呼び出し側が結果を必要としない場合は `void`、行数が必要なら `AffectedRows`、insert metadata が必要なら `InsertedRow`、独自の結果を作りたい場合は `PostQueryInterface` を使います。

### Q: どんな戻り値型を使えますか？

**A: 戻り値型が意図の宣言です。** 代表的な形は次の通りです。

- `array`: associative row list
- `?array` + `type: 'row'`: 1 行、または `null`
- docblock の `array<User>`: hydrated entity list
- `?User` + `type: 'row'`: 1 つの hydrated entity、または `null`
- `void`、`AffectedRows`、`InsertedRow`: DML
- `Pages<User>`: lazy pagination
- `PostQueryInterface` 実装: custom wrapper

完全な対応表は [マニュアル]({{ '/reference/' | relative_url }}) を参照してください。

## Rich Objects

### Q: Ray.MediaQuery の rich domain object とは何ですか？

**A: SQL の結果から作られる、型を持った PHP オブジェクトです。多くの場合 factory を通して作ります。** クエリは単なる data bag ではなく、計算値、ラベル、predicate、service-backed な振る舞いを持つオブジェクトを返せます。

旧 README の中心にあった考え方です。SQL は data access を担い、PHP は型と振る舞いを表します。

### Q: Factory は DI できますか？

**A: はい。** Factory は DI container から service を受け取り、より豊かなオブジェクトを組み立てられます。tax calculator、rule engine、formatter、clock、currency converter、provider、value object builder などです。

ただし I/O は意図的に扱います。list query の factory が各行で外部 API を呼ぶと、N+1 の変形になります。batching、provider 側の cache、事前ロード、または detail query への分離を検討します。

### Q: ビジネスロジックはどこに置きますか？

**A: 読み取り側の派生判断は read model へ、書き込み側の不変条件は write model へ置きます。** BDR object は、現在の projection に関する label、total、visibility、priority などを答えられます。

一方で、状態変更を実行してよいかの最終判断をそこだけに置くべきではありません。それは command/write path の責任です。

### Q: BDR object は DTO、Entity、Value Object のどれですか？

**A: 特定の projection のための read model です。** クエリ結果から作られるので DTO のように見えますが、振る舞いを持てます。変更追跡や保存をしないので、persistence Entity ではありません。

identity が重要なら ID を持てます。値としての等価性が重要なら value object のように扱えます。重要なのは、データベース行のライフサイクル全体ではなく、1 つの query result shape を表すことです。

## アーキテクチャ

### Q: 変更したオブジェクトをどうやって DB に保存しますか？

**A: 明示的な write path を使います。** BDR の read object は、画面、帳票、API response、use case に合わせた projection です。オブジェクト自身は保存しません。

現在の状態や表示可能な操作を見せるために projection を読みます。状態変更には command または application write use case を呼びます。その write path が書き込み側の不変条件を検証し、`UPDATE`、`INSERT`、`DELETE`、または別の手段で永続化します。

### Q: BDR は CQRS ですか？

**A: BDR は CQRS の Query side に適用できます。ただし、より正確には rich read-model pattern です。** CQRS は、read repository と write repository を別フォルダに置くことや、database を分けることが本質ではありません。読み取りと書き込みでは最適なモデルが違う、というのが中心です。

BDR は read model を明示します。SQL が projection を定義し、PHP が型と振る舞いを与えます。command/write model はアプリケーション側の設計です。

### Q: 画面ごとに SQL を分けるのは DRY 違反では？

**A: それだけでは DRY 違反ではありません。** 2 つの画面が違う問いを持つなら、1 つの汎用 query や 1 つの Entity model で両方を満たすより、別々の SQL の方が明確なことが多いです。

列名の繰り返しよりも、違う理由で変わる use case を結合する方が高くつきます。共通部分に安定した意味があるときだけ抽出します。

### Q: 段階的に導入できますか？

**A: はい。** まず、既存モデルでは扱いづらい read path から始めます。report、search result、dashboard、derived field の多い detail screen などです。

既存の write code はそのままにできます。1 つの interface、1 つの SQL file、1 つの return type を追加します。明確になったなら繰り返します。

### Q: 既存の ORM と併用できますか？

**A: はい。** Ray.MediaQuery は persistence layer 全体の置き換えを要求しません。既存の ORM や write model を残し、明示的な SQL の方が読みやすい read path に Ray.MediaQuery を使えます。

重要なのは概念上の境界です。読み取りは、書き込みモデルに無理に通さず、読み取り専用のモデルを持てます。

## テスト

### Q: Ray.MediaQuery のコードはどうテストしますか？

**A: レイヤーごとに独立してテストします。** SQL test は期待する行と列を返すかを確認します。Factory test は構築と変換を確認します。Domain object test は database なしで振る舞いを確認します。

Application test では query interface を fake します。契約が typed return value を持つ interface なので、fake は小さく use case に集中できます。

### Q: なぜ AI-friendly なのですか？

**A: 契約が読めるからです。** Interface はアプリケーションが何を求めるかを示します。SQL file は実際に何が実行されるかを示します。Return type は何が返るかを示します。

AI assistant が隠れた query generation を逆算する必要がありません。

## 境界

### Q: Ray.MediaQuery を使わない方がよい場面はありますか？

**A: 現在のモデルがすでに単純なら、無理に追加する必要はありません。** 1 つのテーブルに素直に対応する小さな CRUD screen なら、専用の rich read model は不要かもしれません。

Ray.MediaQuery は、SQL が projection を明確に表せて、PHP の型や factory がその projection を安全に扱えるときに特に有効です。

### Q: `SqlQueryInterface` を直接使うのはいつですか？

**A: Interface method が最適な境界でない、高度または動的なケースです。** 通常の application code では `#[DbQuery]` interface を優先します。契約が明示的で、fake しやすいからです。

Direct SQL execution は、infrastructure code、dynamic query dispatch、lower-level adapter で有用です。
