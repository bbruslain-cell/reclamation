import { createApp } from 'vue/dist/vue.esm-bundler.js';

const appRoot = document.getElementById('app');
const stateNode = document.getElementById('public-demand-state');

if (appRoot && stateNode) {
    let state = {};

    try {
        state = JSON.parse(stateNode.textContent || '{}');
    } catch (error) {
        appRoot.removeAttribute('v-cloak');
        console.error('Etat du formulaire public invalide.', error);
    }

    const old = state.old || {};
    const serverErrors = state.errors || {};
    const categoriesByType = state.categoriesByType || { reclamation: [], autre: ['Autre'] };
    const establishments = Array.isArray(state.establishments) ? state.establishments : [];

    const asString = (value) => (typeof value === 'string' ? value : '');
    const normalizeStatus = (value) => asString(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
    const normalizeSearch = (value) => normalizeStatus(value).replace(/[^a-z0-9]+/g, ' ').trim();

    createApp({
        data() {
            return {
                form: {
                    nom: asString(old.nom),
                    prenom: asString(old.prenom),
                    email: asString(old.email),
                    statut_usager: asString(old.statut_usager),
                    pays: asString(old.pays),
                    etablissement: asString(old.etablissement),
                    type_demande_code: 'reclamation',
                    categorie: asString(old.categorie),
                    objet: asString(old.objet),
                    message: asString(old.message),
                    consentement: Boolean(old.consentement),
                },
                errors: {
                    nom: serverErrors.nom || null,
                    prenom: serverErrors.prenom || null,
                    email: serverErrors.email || null,
                    statut_usager: serverErrors.statut_usager || null,
                    pays: serverErrors.pays || null,
                    etablissement: serverErrors.etablissement || null,
                    categorie: serverErrors.categorie || null,
                    objet: serverErrors.objet || null,
                    message: serverErrors.message || null,
                    piece_jointe: serverErrors.piece_jointe || null,
                    consentement: serverErrors.consentement || null,
                },
                categories: [],
                establishmentQuery: asString(old.etablissement),
                showEstablishmentOptions: false,
                submitting: false,
            };
        },
        mounted() {
            this.fillCategories();
            this.setupDropzone();
        },
        computed: {
            requiresEtablissement() {
                return ['eleve', 'etudiant'].includes(normalizeStatus(this.form.statut_usager));
            },
            establishmentOptions() {
                return [...new Set([...establishments, 'Autre'])];
            },
            filteredEstablishments() {
                const query = normalizeSearch(this.establishmentQuery);

                if (!query) {
                    return this.establishmentOptions.slice(0, 40);
                }

                const matches = establishments
                    .filter((establishment) => normalizeSearch(establishment).includes(query))
                    .slice(0, 39);
                const hasExactMatch = matches.some((establishment) => (
                    normalizeSearch(establishment) === query
                ));

                return hasExactMatch ? matches : [...matches, 'Autre'];
            },
        },
        methods: {
            fillCategories() {
                this.categories = categoriesByType.reclamation || [];
                if (!this.categories.includes(this.form.categorie)) {
                    this.form.categorie = '';
                }
            },
            handleStatutUsagerChange() {
                this.clearError('statut_usager');
                if (!this.requiresEtablissement) {
                    this.clearError('etablissement');
                }
            },
            openEstablishmentList() {
                this.showEstablishmentOptions = true;
            },
            closeEstablishmentList() {
                window.setTimeout(() => {
                    this.showEstablishmentOptions = false;
                }, 120);
            },
            handleEstablishmentSearch() {
                this.form.etablissement = '';
                this.showEstablishmentOptions = true;
                this.clearError('etablissement');
            },
            selectEstablishment(establishment) {
                this.form.etablissement = establishment;
                this.establishmentQuery = establishment;
                this.showEstablishmentOptions = false;
                this.clearError('etablissement');
            },
            clearEstablishment() {
                this.form.etablissement = '';
                this.establishmentQuery = '';
                this.showEstablishmentOptions = true;
                this.clearError('etablissement');
            },
            ensureSelectedEstablishment() {
                const query = this.establishmentQuery.trim();

                if (!this.requiresEtablissement && query === '') {
                    return true;
                }

                if (this.form.etablissement) {
                    return true;
                }

                const exactMatch = this.establishmentOptions.find((establishment) => (
                    normalizeSearch(establishment) === normalizeSearch(query)
                ));

                if (exactMatch) {
                    this.selectEstablishment(exactMatch);
                    return true;
                }

                this.errors.etablissement = 'Selectionnez un etablissement dans la liste ou Autre.';
                this.showEstablishmentOptions = true;
                return false;
            },
            applyCategory() {
                this.form.objet = this.form.categorie;
                if (this.form.objet) {
                    this.clearError('categorie');
                    this.clearError('objet');
                }
            },
            clearError(field) {
                if (this.errors[field]) {
                    this.errors[field] = null;
                }
            },
            handleSubmit(event) {
                if (!this.ensureSelectedEstablishment()) {
                    event.preventDefault();
                    return;
                }

                if (this.submitting) {
                    event.preventDefault();
                    return;
                }

                this.submitting = true;
                window.setTimeout(() => {
                    this.submitting = false;
                }, 8000);
            },
            setupDropzone() {
                document.addEventListener('dragover', (event) => event.preventDefault());
                document.addEventListener('drop', (event) => event.preventDefault());

                const dropzone = document.getElementById('dropzone');
                const input = document.getElementById('piece_jointe');
                const list = document.getElementById('file-list');
                if (!dropzone || !input || !list) {
                    return;
                }

                const maxSize = 3.5 * 1024 * 1024;
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

                const refreshList = () => {
                    list.textContent = '';
                    Array.from(input.files || []).forEach((file) => {
                        const extension = (file.name.split('.').pop() || '').toUpperCase();
                        const size = (file.size / 1024).toFixed(0);

                        const row = document.createElement('div');
                        row.className = 'flex items-center gap-2.5 bg-sky-50 border border-sky-100 text-navy-500 text-xs px-3 py-2 rounded-lg';

                        const ext = document.createElement('span');
                        ext.className = 'w-7 h-7 rounded bg-sky-100 flex items-center justify-center font-medium text-[10px] text-sky-400 flex-shrink-0';
                        ext.textContent = extension;

                        const name = document.createElement('span');
                        name.className = 'flex-1 font-medium truncate';
                        name.textContent = file.name;

                        const sizeLabel = document.createElement('span');
                        sizeLabel.className = 'text-sky-400';
                        sizeLabel.textContent = `${size} Ko`;

                        row.append(ext, name, sizeLabel);
                        list.appendChild(row);
                    });
                };

                const applyFiles = (files) => {
                    if (!files || files.length === 0) {
                        return;
                    }

                    const file = files[0];

                    if (file.size > maxSize) {
                        this.errors.piece_jointe = `Fichier trop lourd (${(file.size / 1024 / 1024).toFixed(1)} Mo). Maximum autorisé : 3,5 Mo.`;
                        return;
                    }

                    if (!allowedTypes.includes(file.type)) {
                        this.errors.piece_jointe = 'Format non accepté. Utilisez des formats PDF, JPG ou PNG.';
                        return;
                    }

                    this.errors.piece_jointe = null;
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    input.files = dataTransfer.files;
                    refreshList();
                };

                dropzone.addEventListener('click', () => input.click());
                dropzone.addEventListener('keypress', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        input.click();
                    }
                });
                dropzone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    dropzone.classList.add('dropzone-active');
                });
                dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dropzone-active'));
                dropzone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    dropzone.classList.remove('dropzone-active');
                    if (event.dataTransfer?.files?.length) {
                        applyFiles(event.dataTransfer.files);
                    }
                });
                input.addEventListener('change', () => applyFiles(input.files));
            },
        },
    }).mount(appRoot);
}
