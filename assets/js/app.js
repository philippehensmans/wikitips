/**
 * WikiTips - JavaScript principal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Confirmation de suppression
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Auto-resize des textareas
    document.querySelectorAll('textarea').forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});

/**
 * Fonction utilitaire pour les appels API
 */
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    const response = await fetch('/api/' + endpoint, options);
    return response.json();
}

/**
 * Supprimer un article
 */
async function deleteArticle(id, redirectUrl = '/articles.php') {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        const result = await apiCall('articles/' + id, 'DELETE');
        if (result.success) {
            window.location.href = redirectUrl;
        } else {
            alert('Erreur lors de la suppression: ' + (result.message || 'Erreur inconnue'));
        }
    }
}

/**
 * Analyser du contenu via l'API
 */
async function analyzeContent(content, sourceUrl = '') {
    return apiCall('analyze', 'POST', {
        content: content,
        source_url: sourceUrl
    });
}

/**
 * Éditeur de texte riche simple
 */
class RichEditor {
    constructor(textarea) {
        this.textarea = textarea;
        this.createEditor();
    }

    createEditor() {
        // Créer le wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'rich-editor-wrapper';

        // Créer la barre d'outils
        this.toolbar = document.createElement('div');
        this.toolbar.className = 'rich-editor-toolbar';

        const buttons = [
            { cmd: 'bold', icon: 'B', title: 'Gras (Ctrl+B)' },
            { cmd: 'italic', icon: 'I', title: 'Italique (Ctrl+I)' },
            { cmd: 'underline', icon: 'U', title: 'Souligné (Ctrl+U)' },
            { type: 'separator' },
            { cmd: 'insertUnorderedList', icon: '•', title: 'Liste à puces' },
            { cmd: 'insertOrderedList', icon: '1.', title: 'Liste numérotée' },
            { type: 'separator' },
            { cmd: 'insertTable', icon: '▦', title: 'Insérer un tableau', custom: true },
        ];

        buttons.forEach(btn => {
            if (btn.type === 'separator') {
                const sep = document.createElement('span');
                sep.className = 'separator';
                this.toolbar.appendChild(sep);
            } else {
                const button = document.createElement('button');
                button.type = 'button';
                button.innerHTML = btn.icon;
                button.title = btn.title;
                button.style.fontStyle = btn.cmd === 'italic' ? 'italic' : 'normal';
                button.style.textDecoration = btn.cmd === 'underline' ? 'underline' : 'none';
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (btn.custom) {
                        this.handleCustomCommand(btn.cmd);
                    } else {
                        this.execCommand(btn.cmd);
                    }
                });
                this.toolbar.appendChild(button);
            }
        });

        // Créer la zone d'édition
        this.content = document.createElement('div');
        this.content.className = 'rich-editor-content';
        this.content.contentEditable = true;
        this.content.innerHTML = this.convertToHtml(this.textarea.value) || '<p><br></p>';

        // Assembler
        this.wrapper.appendChild(this.toolbar);
        this.wrapper.appendChild(this.content);

        // Remplacer le textarea
        this.textarea.style.display = 'none';
        this.textarea.parentNode.insertBefore(this.wrapper, this.textarea.nextSibling);

        // Événements
        this.content.addEventListener('input', () => this.syncToTextarea());
        this.content.addEventListener('keydown', (e) => this.handleKeydown(e));

        // Synchroniser au chargement
        this.syncToTextarea();
    }

    execCommand(cmd, value = null) {
        this.content.focus();
        document.execCommand(cmd, false, value);
        this.syncToTextarea();
    }

    handleCustomCommand(cmd) {
        switch (cmd) {
            case 'insertTable':
                this.insertTable();
                break;
        }
    }

    insertTable() {
        const rows = prompt('Nombre de lignes :', '3');
        if (!rows) return;
        const cols = prompt('Nombre de colonnes :', '3');
        if (!cols) return;

        const numRows = parseInt(rows, 10) || 3;
        const numCols = parseInt(cols, 10) || 3;

        let html = '<table><thead><tr>';
        for (let c = 0; c < numCols; c++) {
            html += '<th>En-tête</th>';
        }
        html += '</tr></thead><tbody>';
        for (let r = 0; r < numRows - 1; r++) {
            html += '<tr>';
            for (let c = 0; c < numCols; c++) {
                html += '<td>Cellule</td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table><p><br></p>';

        this.content.focus();
        document.execCommand('insertHTML', false, html);
        this.syncToTextarea();
    }

    handleKeydown(e) {
        // Raccourcis clavier
        if (e.ctrlKey || e.metaKey) {
            switch (e.key.toLowerCase()) {
                case 'b':
                    e.preventDefault();
                    this.execCommand('bold');
                    break;
                case 'i':
                    e.preventDefault();
                    this.execCommand('italic');
                    break;
                case 'u':
                    e.preventDefault();
                    this.execCommand('underline');
                    break;
            }
        }
    }

    syncToTextarea() {
        this.textarea.value = this.content.innerHTML;
    }

    setContent(html) {
        this.content.innerHTML = this.convertToHtml(html) || '<p><br></p>';
        this.syncToTextarea();
    }

    /**
     * Convertit le texte brut avec des retours à la ligne en HTML avec des paragraphes.
     * Si le contenu contient déjà des balises HTML, le retourne tel quel.
     */
    convertToHtml(text) {
        if (!text || !text.trim()) {
            return '';
        }

        // Si le contenu contient déjà des balises HTML de bloc, le retourner tel quel
        if (/<(p|br|div|ul|ol|li|table|h[1-6])[>\s/]/i.test(text)) {
            return text;
        }

        // Convertir les retours à la ligne en paragraphes
        const paragraphs = text.split(/\n\n+/); // Séparer par double retour à la ligne
        return paragraphs.map(para => {
            // Pour chaque paragraphe, convertir les retours simples en <br>
            const content = para.trim().replace(/\n/g, '<br>');
            return content ? '<p>' + content + '</p>' : '';
        }).filter(p => p).join('');
    }
}

/**
 * Initialise les éditeurs riches sur les éléments avec la classe 'rich-editor'
 */
function initRichEditors() {
    document.querySelectorAll('textarea.rich-editor').forEach(textarea => {
        if (!textarea.dataset.richEditorInit) {
            new RichEditor(textarea);
            textarea.dataset.richEditorInit = 'true';
        }
    });
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', initRichEditors);
