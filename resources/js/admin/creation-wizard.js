import '../../css/creation-wizard.css';

function initCreationWizard(root) {
    const form = root.closest('form');
    const task = root.dataset.kind === 'task';
    const find = (selector) => root.querySelector(selector);
    const source = (step) => find(`[data-wizard-source="${step}"]`);
    const topic = form.querySelector(task ? '#task_name' : '#title');
    const prompt = form.querySelector(task ? '#prompt_id' : '#article-ai-prompt');
    const platform = form.querySelector(task ? '#target_platform' : '#article-ai-platform');
    const settings = find('[data-wizard-settings]');
    const next = find('[data-wizard-next]');
    const back = find('[data-wizard-back]');
    const submit = find('[data-wizard-submit]');
    const error = find('[data-wizard-error]');
    const storageKey = `geoflow.creation.${task ? 'task' : 'article'}`;
    let step = 0;
    let busy = false;

    // Move the existing controls, retaining their names, values and event handlers.
    const originalChildren = Array.from(form.children).filter((node) => node !== root && node.tagName !== 'INPUT');
    if (task) {
        originalChildren.forEach((node) => {
            if (node.querySelector('[type="submit"]')) {
                node.hidden = true;
                return;
            }
            const targetStep = node.querySelector('#task_name, #ai_model_id') ? 0
                : node.querySelector('[name="publish_scope"]') ? 1 : 2;
            source(targetStep).append(node);
        });
    } else {
        originalChildren.forEach((node) => source(2).append(node));
        const assistant = form.querySelector('#article-create-assistant');
        if (assistant) source(0).append(assistant);
        source(2).querySelectorAll('[type="submit"]').forEach((node) => { node.parentElement.hidden = true; });
    }
    find('[data-wizard-topic]').append(topic);
    topic.classList.add('creation-topic');
    topic.setAttribute('aria-label', task ? '这个任务主要想写什么？' : '这篇文章主要想说什么？');
    if (platform) {
        platform.closest('div').hidden = true;
        const choices = Array.from(platform.options).filter((option) => option.value);
        choices.forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = option.textContent;
            button.dataset.platform = option.value;
            button.addEventListener('click', () => {
                platform.value = platform.value === option.value ? '' : option.value;
                platform.dispatchEvent(new Event('change', { bubbles: true }));
                syncChoices();
            });
            find('[data-wizard-platforms]').append(button);
        });
    }
    if (prompt) {
        const options = Array.from(prompt.options).filter((option) => option.value);
        // These are real prompt templates from the existing catalog.
        for (const option of options) {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.prompt = option.value;
            const dot = document.createElement('span');
            dot.className = 'creation-tone-dot';
            const label = document.createElement('strong');
            label.textContent = option.textContent;
            const help = document.createElement('span');
            help.textContent = '应用此写作模板';
            button.append(dot, label, help);
            button.addEventListener('click', () => {
                prompt.value = prompt.value === option.value ? '' : option.value;
                prompt.dispatchEvent(new Event('change', { bubbles: true }));
                syncChoices();
            });
            find('[data-wizard-tones]').append(button);
        }
        if (!options.length) find('[data-wizard-tones]').textContent = '暂无写作模板，可以使用默认风格生成。';
        prompt.addEventListener('change', syncChoices);
    }

    function syncChoices() {
        root.querySelectorAll('[data-platform], [data-prompt]').forEach((button) => {
            const selected = button.dataset.platform !== undefined
                ? platform?.value === button.dataset.platform : prompt?.value === button.dataset.prompt;
            button.setAttribute('aria-pressed', String(selected));
        });
    }

    function showStep(value, focus = true) {
        step = value;
        root.querySelectorAll('[data-wizard-step]').forEach((panel, index) => { panel.hidden = index !== step; });
        root.querySelectorAll('[data-wizard-indicator]').forEach((indicator, index) => {
            indicator.dataset.state = index === step ? 'active' : index < step ? 'complete' : 'pending';
            if (index === step) indicator.setAttribute('aria-current', 'step');
            else indicator.removeAttribute('aria-current');
            indicator.querySelector('[data-wizard-number]').textContent = index < step ? '✓' : index + 1;
        });
        back.hidden = step === 0;
        next.hidden = step === 2;
        submit.hidden = step !== 2;
        error.hidden = true;
        if (focus) {
            const panel = find(`[data-wizard-step="${step}"]`);
            panel.tabIndex = -1;
            panel.focus({ preventScroll: true });
        }
        if (step === 2) updatePreview();
    }

    function content() {
        return window.geoArticleEditorAssistantBridge?.getValue?.() || form.querySelector('#content-textarea')?.value || '';
    }

    function updatePreview() {
        find('[data-wizard-preview-title]').textContent = topic.value;
        const text = task
            ? Array.from(form.querySelectorAll('#title_library_id, #ai_model_id, #article_limit, #publish_interval')).map((field) => {
                const label = form.querySelector(`label[for="${field.id}"]`)?.textContent.replace('*', '').trim() || field.id;
                return `${label}：${field.selectedOptions?.[0]?.textContent.trim() || field.value}`;
            }).join('\n')
            : content();
        find('[data-wizard-preview-content]').textContent = text || '尚未填写正文，请展开下方编辑器填写，或返回生成内容。';
        const tags = find('[data-wizard-preview-tags]');
        tags.replaceChildren();
        const labels = task
            ? Array.from(form.querySelectorAll('[data-distribution-channel-input]:checked:not(:disabled)')).map((input) => input.closest('label')?.innerText.trim())
            : platform?.value ? [platform.selectedOptions[0].textContent] : [];
        if (task && form.querySelector('[name="publish_scope"]:checked')?.value !== 'distribution_only') labels.unshift('本站');
        labels.filter(Boolean).forEach((label) => {
            const tag = document.createElement('span');
            tag.textContent = label;
            tags.append(tag);
        });
        submit.textContent = task ? '确认创建任务' : form.querySelector('#status')?.value === 'published' ? '确认发布文章' : '确认保存文章';
        find('[data-wizard-hint]').textContent = task ? '创建后按任务状态、审核要求与分发策略执行' : '文章按所选状态保存，发布仍需通过系统审核与内容检查';
    }

    function reveal(field) {
        const panel = field.closest('[data-wizard-step]');
        if (panel) showStep(Number(panel.dataset.wizardStep), false);
        for (let node = field.parentElement; node && node !== root; node = node.parentElement) {
            if (node.tagName === 'DETAILS') node.open = true;
        }
    }

    function validate(panel = root) {
        topic.setCustomValidity(topic.value.trim() ? '' : '请填写想写的内容。');
        const invalid = Array.from(panel.querySelectorAll('input, select, textarea')).find((field) => !field.disabled && !field.checkValidity());
        if (!invalid) return true;
        reveal(invalid);
        invalid.reportValidity();
        return false;
    }

    next.addEventListener('click', async () => {
        if (busy || !validate(find(`[data-wizard-step="${step}"]`))) return;
        if (step === 1 && !task && !content().trim()) {
            const model = form.querySelector('#article-ai-model');
            if (!model?.value) {
                showStep(0);
                settings.open = true;
                model?.focus();
                error.textContent = '请选择 AI 模型后生成文章，或在确认页编辑正文。';
                error.hidden = false;
                return;
            }
            busy = true;
            next.disabled = true;
            back.disabled = true;
            next.textContent = '正在生成文章…';
            let success = false;
            try {
                success = await window.geoArticleCreationAssistant?.generate();
            } finally {
                busy = false;
                next.disabled = false;
                back.disabled = false;
                next.textContent = '下一步';
            }
            if (!success) {
                showStep(0);
                settings.open = true;
                error.textContent = '文章未生成完成，请查看生成提示后重试。';
                error.hidden = false;
                return;
            }
        }
        showStep(step + 1);
    });
    back.addEventListener('click', () => showStep(step - 1));
    find('[data-wizard-manual]')?.addEventListener('click', () => {
        if (!validate(find('[data-wizard-step="0"]'))) return;
        showStep(2);
        find('[data-wizard-review-settings]').open = true;
    });
    topic.addEventListener('input', () => topic.setCustomValidity(''));
    root.querySelectorAll('[data-wizard-option]').forEach((button) => button.addEventListener('click', () => {
        settings.open = true;
        const target = button.dataset.wizardOption;
        if (target === 'preferences') {
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
                for (const [id, value] of Object.entries(saved)) {
                    const field = form.querySelector(`#${CSS.escape(id)}`);
                    if (field?.querySelector(`option[value="${CSS.escape(value)}"]`)) {
                        field.value = value;
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
                error.textContent = Object.keys(saved).length ? '已套用保存的写作偏好。' : '还没有保存过写作偏好，确认时可勾选保存。';
            } catch { error.textContent = '当前浏览器无法读取写作偏好。'; }
            error.hidden = false;
            syncChoices();
        } else {
            const field = target === 'prompt' ? prompt : form.querySelector(task ? '[data-knowledge-base-input]' : '#article-ai-knowledge-base');
            field?.focus();
        }
    }));
    form.addEventListener('invalid', (event) => reveal(event.target), true);
    form.addEventListener('submit', (event) => {
        if (busy || step !== 2) {
            event.preventDefault();
            if (!busy) next.click();
            return;
        }
        if (!validate()) { event.preventDefault(); return; }
        if (!task && !content().trim()) {
            event.preventDefault();
            find('[data-wizard-review-settings]').open = true;
            error.textContent = '请先填写文章正文。';
            error.hidden = false;
            return;
        }
        if (find('[data-wizard-save]').checked) {
            const values = {};
            [prompt, platform, form.querySelector(task ? '#ai_model_id' : '#article-ai-model'), form.querySelector('#article-ai-knowledge-base')].filter(Boolean).forEach((field) => { values[field.id] = field.value; });
            try { localStorage.setItem(storageKey, JSON.stringify(values)); } catch { /* Saving preferences must not block creation. */ }
        }
    });
    form.addEventListener('input', () => { if (step === 2) updatePreview(); });
    form.addEventListener('change', () => { syncChoices(); if (step === 2) updatePreview(); });
    find('[data-wizard-review-settings]').addEventListener('toggle', updatePreview);
    form.classList.add('creation-form');
    root.hidden = false;
    syncChoices();
    showStep(0, false);
    const restoredInvalid = Array.from(form.querySelectorAll('[required]')).find((field) => !field.value);
    if (document.querySelector('.admin-flash-alert.is-error') && restoredInvalid) reveal(restoredInvalid);
}

const init = () => document.querySelectorAll('[data-creation-wizard]').forEach(initCreationWizard);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
