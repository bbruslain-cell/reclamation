import './bootstrap';

const GLOBAL_LOADING_TIMEOUT = 65000;

let globalLoadingBar = null;
let globalLoadingTimeout = null;

const ensureGlobalLoadingBar = () => {
    if (globalLoadingBar?.isConnected) {
        return globalLoadingBar;
    }

    globalLoadingBar = document.createElement('div');
    globalLoadingBar.className = 'anbg-page-loading';
    globalLoadingBar.setAttribute('role', 'progressbar');
    globalLoadingBar.setAttribute('aria-label', 'Chargement en cours');
    globalLoadingBar.setAttribute('aria-hidden', 'true');
    globalLoadingBar.innerHTML = '<span class="anbg-page-loading-bar" aria-hidden="true"></span>';
    document.body.appendChild(globalLoadingBar);

    return globalLoadingBar;
};

const stopGlobalLoading = () => {
    if (globalLoadingTimeout) {
        window.clearTimeout(globalLoadingTimeout);
        globalLoadingTimeout = null;
    }

    if (!globalLoadingBar) {
        return;
    }

    globalLoadingBar.classList.remove('is-active');
    globalLoadingBar.setAttribute('aria-hidden', 'true');
    document.documentElement.removeAttribute('aria-busy');
};

const startGlobalLoading = () => {
    const loadingBar = ensureGlobalLoadingBar();

    if (globalLoadingTimeout) {
        window.clearTimeout(globalLoadingTimeout);
    }

    loadingBar.setAttribute('aria-hidden', 'false');
    loadingBar.classList.remove('is-active');
    void loadingBar.offsetWidth;
    loadingBar.classList.add('is-active');
    document.documentElement.setAttribute('aria-busy', 'true');

    globalLoadingTimeout = window.setTimeout(stopGlobalLoading, GLOBAL_LOADING_TIMEOUT);
};

const isPageNavigation = (event, link) => {
    if (
        event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
        || link.hasAttribute('download')
        || link.dataset.noLoading !== undefined
    ) {
        return false;
    }

    const target = (link.getAttribute('target') || '').toLowerCase();
    if (target && target !== '_self') {
        return false;
    }

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || /^(javascript:|mailto:|tel:)/i.test(href)) {
        return false;
    }

    const destination = new URL(link.href, window.location.href);
    const current = new URL(window.location.href);

    return destination.protocol === 'http:' || destination.protocol === 'https:'
        ? destination.origin !== current.origin
            || destination.pathname !== current.pathname
            || destination.search !== current.search
        : false;
};

const initAnbgGlobalLoading = () => {
    if (window.anbgGlobalLoadingReady) {
        return;
    }

    window.anbgGlobalLoadingReady = true;
    ensureGlobalLoadingBar();

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.dataset.noLoading !== undefined) {
            return;
        }

        startGlobalLoading();
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (link && isPageNavigation(event, link)) {
            startGlobalLoading();
        }
    });

    window.addEventListener('beforeunload', startGlobalLoading);
    window.addEventListener('pageshow', stopGlobalLoading);
};

const initAnbgConfirmations = () => {
    if (window.anbgConfirmationsReady) {
        return;
    }

    window.anbgConfirmationsReady = true;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) {
            return;
        }

        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    }, true);
};

const resetSubmitButton = (button) => {
    button.disabled = false;
    button.removeAttribute('aria-busy');

    if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
    }
};

const initAnbgSubmitLoading = (root = document) => {
    root.querySelectorAll('form:not([data-submit-loading-ready])').forEach((form) => {
        const button = form.querySelector('button[type="submit"][data-submit-loading]');
        if (!button) {
            return;
        }

        form.dataset.submitLoadingReady = '1';
        let submitStarted = false;

        form.addEventListener('submit', (event) => {
            if (submitStarted) {
                event.preventDefault();
                return;
            }

            submitStarted = true;
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            const spinner = document.createElement('span');
            spinner.className = 'anbg-submit-spinner';
            spinner.setAttribute('aria-hidden', 'true');

            const label = document.createElement('span');
            label.textContent = button.dataset.loadingLabel || 'Envoi en cours...';

            button.replaceChildren(spinner, label);

            window.setTimeout(() => {
                submitStarted = false;
                resetSubmitButton(button);
            }, 15000);
        });
    });
};

const previewModalHtml = () => `
    <div class="anbg-attachment-preview-backdrop" data-attachment-preview-close></div>
    <section class="anbg-attachment-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="anbg-attachment-preview-title">
        <header class="anbg-attachment-preview-header">
            <div class="anbg-attachment-preview-title-wrap">
                <p class="anbg-attachment-preview-eyebrow">Aperçu de la pièce jointe</p>
                <h2 id="anbg-attachment-preview-title" class="anbg-attachment-preview-title">Pièce jointe</h2>
            </div>
            <div class="anbg-attachment-preview-actions">
                <a id="anbg-attachment-preview-download" class="anbg-attachment-preview-download" href="#">Télécharger</a>
                <button type="button" class="anbg-attachment-preview-close" data-attachment-preview-close aria-label="Fermer l'aperçu">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </header>
        <div id="anbg-attachment-preview-content" class="anbg-attachment-preview-content"></div>
    </section>
`;

const ensureAttachmentPreviewModal = () => {
    let modal = document.getElementById('anbg-attachment-preview-modal');
    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.id = 'anbg-attachment-preview-modal';
    modal.className = 'anbg-attachment-preview hidden';
    modal.innerHTML = previewModalHtml();
    document.body.appendChild(modal);

    modal.addEventListener('click', (event) => {
        if (event.target.closest('[data-attachment-preview-close]')) {
            closeAttachmentPreview();
        }
    });

    return modal;
};

const attachmentDownloadUrl = (previewUrl) => {
    const url = new URL(previewUrl, window.location.origin);
    url.searchParams.delete('preview');

    return url.toString();
};

const isImageAttachment = (name = '') => /\.(png|jpe?g)$/i.test(name);

const closeAttachmentPreview = () => {
    const modal = document.getElementById('anbg-attachment-preview-modal');
    if (!modal) {
        return;
    }

    const content = modal.querySelector('#anbg-attachment-preview-content');
    if (content) {
        content.innerHTML = '';
    }

    modal.classList.add('hidden');
};

const openAttachmentPreview = (url, name = 'Pièce jointe') => {
    const modal = ensureAttachmentPreviewModal();
    const title = modal.querySelector('#anbg-attachment-preview-title');
    const download = modal.querySelector('#anbg-attachment-preview-download');
    const content = modal.querySelector('#anbg-attachment-preview-content');
    const normalizedName = name || 'Pièce jointe';

    if (title) {
        title.textContent = normalizedName;
    }

    if (download) {
        download.href = attachmentDownloadUrl(url);
    }

    if (content) {
        content.innerHTML = '';
        if (isImageAttachment(normalizedName)) {
            const image = document.createElement('img');
            image.src = url;
            image.alt = normalizedName;
            image.className = 'anbg-attachment-preview-image';
            content.appendChild(image);
        } else {
            const frame = document.createElement('iframe');
            frame.src = url;
            frame.title = normalizedName;
            frame.className = 'anbg-attachment-preview-frame';
            content.appendChild(frame);
        }
    }

    modal.classList.remove('hidden');
    modal.querySelector('.anbg-attachment-preview-close')?.focus();
};

const initAnbgAttachmentPreview = () => {
    if (window.anbgAttachmentPreviewReady) {
        return;
    }

    window.anbgAttachmentPreviewReady = true;

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-attachment-preview]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        openAttachmentPreview(trigger.href, trigger.dataset.attachmentName || trigger.textContent.trim());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAttachmentPreview();
        }
    });
};

window.initAnbgSubmitLoading = initAnbgSubmitLoading;
window.initAnbgAttachmentPreview = initAnbgAttachmentPreview;
window.startAnbgLoading = startGlobalLoading;
window.stopAnbgLoading = stopGlobalLoading;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initAnbgConfirmations();
        initAnbgSubmitLoading(document);
        initAnbgAttachmentPreview();
        initAnbgGlobalLoading();
    });
} else {
    initAnbgConfirmations();
    initAnbgSubmitLoading(document);
    initAnbgAttachmentPreview();
    initAnbgGlobalLoading();
}
