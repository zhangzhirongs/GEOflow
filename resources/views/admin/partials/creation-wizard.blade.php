<div data-creation-wizard data-kind="{{ $kind }}" hidden>
    <header class="creation-brand">
        <span class="creation-logo">G</span><strong>GEOFlow</strong>
        <span class="creation-kind">{{ $kind === 'task' ? '创建任务' : '创建文章' }}</span>
    </header>
    <nav class="creation-steps" aria-label="创建进度">
        @foreach (['想写什么', '给谁看', '确认发布'] as $label)
            <span data-wizard-indicator><span data-wizard-number>{{ $loop->iteration }}</span><span>{{ $label }}</span></span>
        @endforeach
    </nav>
    <section data-wizard-step="0" class="creation-body">
        <label class="creation-label" for="{{ $kind === 'task' ? 'task_name' : 'title' }}">{{ $kind === 'task' ? '这个任务主要想写什么？' : '这篇文章主要想说什么？' }}</label>
        <div data-wizard-topic></div>
        <div class="creation-options">
            <span>可选</span>
            <button type="button" data-wizard-option="knowledge">＋ 参考品牌资料</button>
            <button type="button" data-wizard-option="preferences">＋ 套用往期风格</button>
            <button type="button" data-wizard-option="prompt">＋ 选个写作模板</button>
        </div>
        <details data-wizard-settings>
            <summary>{{ $kind === 'task' ? '任务与生成设置' : '品牌资料与 AI 生成设置' }}</summary>
            <div data-wizard-source="0" class="creation-settings"></div>
        </details>
        @if ($kind === 'article')
            <button type="button" data-wizard-manual class="creation-manual">已有内容？直接编辑正文</button>
        @endif
    </section>
    <section data-wizard-step="1" class="creation-body" hidden>
        <h2 class="creation-label">想发到哪些平台？</h2>
        <p class="creation-help">{{ $kind === 'task' ? '可多选分发渠道，按所选策略执行任务。' : '选择内容适配平台，保存文章后可继续安排分发。' }}</p>
        <div data-wizard-platforms class="creation-platforms"></div>
        <div data-wizard-source="1" class="creation-settings"></div>
        <h2 class="creation-label creation-tone-heading">希望文章是什么感觉？</h2>
        <p class="creation-help">选择已有写作模板，生成时应用对应风格。</p>
        <div data-wizard-tones class="creation-tones"></div>
    </section>
    <section data-wizard-step="2" class="creation-body" hidden>
        <article class="creation-preview">
            <span class="creation-check">{{ $kind === 'task' ? '待确认任务配置' : '内容预览 · 保存时执行内容检查' }}</span>
            <h2 data-wizard-preview-title></h2>
            <p data-wizard-preview-content></p>
            <div data-wizard-preview-tags class="creation-tags"></div>
        </article>
        <label class="creation-save"><input type="checkbox" data-wizard-save> 把这次的写法存为偏好，下次可以直接用（当前浏览器）</label>
        <details data-wizard-review-settings>
            <summary>{{ $kind === 'task' ? '检查发布、配图与高级设置' : '编辑正文与发布设置' }}</summary>
            <div data-wizard-source="2" class="creation-settings"></div>
        </details>
    </section>
    <footer class="creation-footer">
        <p data-wizard-error role="alert" hidden></p>
        <div class="creation-actions">
            <button type="button" data-wizard-back hidden>上一步</button>
            <button type="button" data-wizard-next>下一步</button>
            <button type="submit" data-wizard-submit hidden>{{ $kind === 'task' ? '确认创建任务' : '确认保存文章' }}</button>
        </div>
        <p data-wizard-hint class="creation-help">写好后会先给你预览，确认满意才会提交</p>
    </footer>
</div>
