import './bootstrap';

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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initAnbgSubmitLoading(document);
        initAnbgAttachmentPreview();
    });
} else {
    initAnbgSubmitLoading(document);
    initAnbgAttachmentPreview();
}
