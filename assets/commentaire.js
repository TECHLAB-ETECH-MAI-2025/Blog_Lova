document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form.comment-form').forEach(form => {
        form.addEventListener('submit', async e => {
            e.preventDefault(); // bloque le rechargement

            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            if (!response.ok) {
                console.error('Erreur lors de l\'ajout du commentaire.');
                return;
            }

            const data = await response.json();

            if (data.success) {
                const commentaireHtml = `
                    <div class="alert alert-light border border-secondary rounded-3 shadow-sm mb-3">
                        <strong class="text-primary d-block">${data.commentaire.auteur}</strong>
                        <span>${data.commentaire.contenu}</span>
                    </div>
                `;

                // Insère le nouveau commentaire avant le formulaire
                form.insertAdjacentHTML('beforebegin', commentaireHtml);

                // Reset formulaire
                form.reset();
            } else {
                console.error('Erreur : ' + (data.message || 'Ajout commentaire échoué'));
            }
        });
    });
});
