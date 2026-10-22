---
name: ai-sdk-development
description: 在涉及 ai-sdk（Laravel 官方第一方 AI SDK）时触发。当构建、编辑 AI 智能体、聊天机器人、文本生成、图像生成、音频/TTS、转录/STT、嵌入、RAG、向量存储、重排序、结构化输出、流式传输、对话记忆、工具、队列、广播，以及跨 OpenAI、Anthropic、Gemini、Azure、Groq、xAI、DeepSeek、Mistral、Ollama、ElevenLabs、Cohere、Jina 和 VoyageAI 的提供方故障转移时激活。当用户提及 ai-sdk、`Laravel\Ai\` 命名空间或本项目的 AI 功能时调用——不适用于直接使用的 Prism PHP 或其他 AI 包。
license: MIT
metadata:
  author: laravel
  display_name: Laravel AI SDK 开发
---

# Laravel AI SDK 开发

Laravel AI SDK（`laravel/ai`）是 Laravel 的官方 AI 包，为代理、图像、音频、转录、嵌入、重排序、向量存储和文件管理提供统一的 API，覆盖多个 AI 提供商。

## 搜索文档

此包是新的。在实现任何功能之前，请始终搜索文档。切勿猜测 API——文档是唯一事实来源。

- 使用与下面文档章节标题相匹配的宽泛、简单查询。
- 不要将包名添加到查询中——包信息会自动共享。使用`test agent fake`，而不是`laravel ai test agent fake`。
- 一次运行多个查询——最相关的结果会优先返回。

### 文档章节

将这些章节标题用作查询词以获得准确结果：

- 简介、安装、配置、提供商支持
- 智能体：提示、对话上下文、结构化输出、附件、流式传输、广播、排队、工具、提供商工具、中间件、匿名智能体、智能体配置
- 图片
- 音频（TTS）
- 转录（STT）
- 嵌入：查询嵌入、缓存嵌入
- 重排序
- 文件
- 向量存储：将文件添加到存储
- 故障转移
- 测试：智能体、图像、音频、转录、嵌入、重排序、文件、向量存储
- 事件

## 决策工作流

在编写代码之前确定正确的入口点：

文本生成还是聊天？→ 使用带有 `Promptable` trait 的智能体类
需要带对话历史的聊天？→ 智能体 + `Conversational` 接口（手动）或 `RemembersConversations` trait（自动）
结构化 JSON 输出？→ 代理 + `HasStructuredOutput` 接口
图像生成？→ `Image::of()->generate()`
音频合成？→ `Audio::of()->generate()`
转录？→ `Transcription::fromPath()->generate()`
嵌入？→ `Embeddings::for()->generate()`
重排序？→ `Reranking::of()->rerank()`
文件存储？→ `Document::fromPath()->put()`
向量存储？→ `Stores::create()`

## 基本用法示例

### 智能体

```php
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

class SalesCoach implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a sales coach.';
    }
}

// 提示词
$response = (new SalesCoach)->prompt('Analyze this transcript...');
echo $response->text;

// 使用依赖注入进行容器解析
$agent = SalesCoach::make(user: $user);

// 按提示词覆盖 provider、model 或 timeout
$response = (new SalesCoach)->prompt(
    'Analyze this transcript...',
    provider: Lab::Anthropic,
    model: 'claude-haiku-4-5-20251001',
    timeout: 120,
);

// 流式传输（从路由返回 SSE 响应）
return (new SalesCoach)->stream('Analyze this transcript...');

// 队列
(new SalesCoach)->queue('Analyze this transcript...')
    ->then(fn ($response) => /* ... */);

// 匿名智能体
use function Laravel\Ai\{agent};

$response = agent(instructions: 'You are a helpful assistant.')->prompt('Hello');
```

### 对话上下文

通过 `Conversational` 接口手动管理对话历史：

```php
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

class SalesCoach implements Agent, Conversational
{
    use Promptable;

    public function __construct(public User $user) {}

    public function instructions(): string { return 'You are a sales coach.'; }

    public function messages(): iterable
    {
        return History::where('user_id', $this->user->id)
            ->latest()->limit(50)->get()->reverse()
            ->map(fn ($m) => new Message($m->role, $m->content))
            ->all();
    }
}
```

通过`RemembersConversations` trait 实现对话自动持久化：

```php
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

class SalesCoach implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function instructions(): string { return 'You are a sales coach.'; }
}

// 开始新对话
$response = (new SalesCoach)->forUser($user)->prompt('Hello!');
$conversationId = $response->conversationId;

// 继续现有对话
$response = (new SalesCoach)->continue($conversationId, as: $user)->prompt('Tell me more.');
```

### 结构化输出

```php
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class Reviewer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string { return 'Review and score content.'; }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema->string()->required(),
            'score' => $schema->integer()->min(1)->max(10)->required(),
        ];
    }
}

$response = (new Reviewer)->prompt('Review this...');
echo $response['score']; // Access like an array
```

### 图片

```php
use Laravel\Ai\Image;

$image = Image::of('A sunset over mountains')
    ->landscape()
    ->quality('high')
    ->generate();

$path = $image->store(); // Store to default disk
```

### 音频

```php
use Laravel\Ai\Audio;

$audio = Audio::of('Hello from Laravel.')
    ->female()
    ->instructions('Speak warmly')
    ->generate();

$path = $audio->store();
```

### 转录

```php
use Laravel\Ai\Transcription;

$transcript = Transcription::fromStorage('audio.mp3')
    ->diarize()
    ->generate();

echo (string) $transcript;
```

### 嵌入

```php
use Laravel\Ai\Embeddings;
use Illuminate\Support\Str;

$response = Embeddings::for(['Text one', 'Text two'])
    ->dimensions(1536)
    ->cache()
    ->generate();

// 通过 Stringable 使用单个字符串
$embedding = Str::of('Napa Valley has great wine.')->toEmbeddings();
```

### 重排序

```php
use Laravel\Ai\Reranking;

$response = Reranking::of(['Django is Python.', 'Laravel is PHP.', 'React is JS.'])
    ->limit(5)
    ->rerank('PHP frameworks');

$response->first()->document; // "Laravel is PHP."
```

### 文件与向量存储

```php
use Laravel\Ai\Files\Document;
use Laravel\Ai\Stores;

// 使用 provider 存储文件
$file = Document::fromPath('/path/to/doc.pdf')->put();

// 创建向量存储并添加文件
$store = Stores::create('Knowledge Base');
$store->add($file->id);
$store->add(Document::fromStorage('manual.pdf')); // Store + add in one step
```

## 智能体配置

### PHP 属性

```php
use Laravel\Ai\Attributes\{Provider, Model, MaxSteps, MaxTokens, Temperature, Timeout};
use Laravel\Ai\Enums\Lab;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')]
#[MaxSteps(10)]
#[MaxTokens(4096)]
#[Temperature(0.7)]
#[Timeout(120)]
class MyAgent implements Agent
{
    use Promptable;
    // ...
}
```

`#[UseCheapestModel]` 和 `#[UseSmartestModel]` 属性也可用于自动模型选择。

### 工具

实现 `HasTools` 接口，并使用 `php artisan make:tool` 搭建工具脚手架：

```php
use Laravel\Ai\Contracts\HasTools;

class MyAgent implements Agent, HasTools
{
    use Promptable;

    public function tools(): iterable
    {
        return [new MyCustomTool];
    }
}
```

### 提供程序工具

```php
use Laravel\Ai\Providers\Tools\{WebSearch, WebFetch, FileSearch};

public function tools(): iterable
{
    return [
        (new WebSearch)->max(5)->allow(['laravel.com']),
        new WebFetch,
        new FileSearch(stores: ['store_id']),
    ];
}
```

### 对话记忆

```php
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Conversational;

class ChatBot implements Agent, Conversational
{
    use Promptable, RemembersConversations;
    // ...
}

$response = (new ChatBot)->forUser($user)->prompt('Hello!');
$response = (new ChatBot)->continue($conversationId, as: $user)->prompt('More...');
```

### 故障转移

```php
$response = (new MyAgent)->prompt('Hello', provider: [Lab::OpenAI, Lab::Anthropic]);
```

## 测试与伪造

每项能力都支持 `fake()`，并带有断言：

```php
use App\Ai\Agents\SalesCoach;
use Laravel\Ai\{Image, Audio, Transcription, Embeddings, Reranking, Files, Stores};

// 智能体
SalesCoach::fake(['Response 1', 'Response 2']);
SalesCoach::assertPrompted('query');
SalesCoach::assertNotPrompted('query');
SalesCoach::assertNeverPrompted();
SalesCoach::fake()->preventStrayPrompts();

// 图像
Image::fake();
Image::assertGenerated(fn ($prompt) => $prompt->contains('sunset'));
Image::assertNothingGenerated();

// 音频
Audio::fake();
Audio::assertGenerated(fn ($prompt) => $prompt->contains('Hello'));

// 转录
Transcription::fake(['Transcribed text.']);
Transcription::assertGenerated(fn ($prompt) => $prompt->isDiarized());

// 嵌入向量
Embeddings::fake();
Embeddings::assertGenerated(fn ($prompt) => $prompt->contains('Laravel'));

// 重排序
Reranking::fake();
Reranking::assertReranked(fn ($prompt) => $prompt->contains('PHP'));

// 文件
Files::fake();
Files::assertStored(fn ($file) => $file->mimeType() === 'text/plain');

// 存储
Stores::fake();
Stores::assertCreated('Knowledge Base');
$store = Stores::get('id');
$store->assertAdded('file_id');
```

## 关键模式

- 命名空间：`Laravel\Ai\`
- 包：`composer require laravel/ai`
- 智能体模式：实现 `Agent` 接口并使用 `Promptable` trait
- 可选接口：`HasTools`、`HasMiddleware`、`HasStructuredOutput`、`Conversational`
- 入口类：`Image`、`Audio`、`Transcription`、`Embeddings`、`Reranking`、`Stores`
- 提供商枚举：`Laravel\Ai\Enums\Lab`（优先于普通字符串）
- Artisan 命令：`php artisan make:agent`、`php artisan make:tool`
- 全局辅助函数：用于匿名代理的 `agent()`

## 常见陷阱

### 命名空间错误

命名空间是 `Laravel\Ai`，不是 `Illuminate\Ai` 或 `Laravel\AI`。

```php
// 正确
use Laravel\Ai\Image;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

// 错误——这些不存在
use Illuminate\Ai\Image;
use Laravel\AI\Agent;
```

### 不支持的提供方能力

调用提供程序不支持的能力会抛出 `LogicException`。请参阅下方的提供程序支持表。

### 切勿直接使用 Prism

使用代理和入口点类（`Image`、`Audio` 等）——不要直接使用 `Prism::text()`。AI SDK 在内部封装了 Prism。

## 提供商支持

| 功能    | 提供商                                                       |
| ---------- | --------------------------------------------------------------- |
| 文本 | OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama |
| 图像     | OpenAI、Gemini、xAI                                            |
| TTS | OpenAI, ElevenLabs |
| STT | OpenAI, ElevenLabs, Mistral |
| 嵌入 | OpenAI、Gemini、Azure、Cohere、Mistral、Jina、VoyageAI         |
| 重排序 | Cohere, Jina |
| 文件      | OpenAI、Anthropic、Gemini                                       |

在代码中使用 `Laravel\Ai\Enums\Lab` 枚举引用提供者，而不是使用普通字符串：

```php
use Laravel\Ai\Enums\Lab;

Lab::Anthropic;
Lab::OpenAI;
Lab::Gemini;
// ...
```