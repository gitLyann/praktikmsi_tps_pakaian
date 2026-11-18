<script>
    (function () {
        const root = document.getElementById(@json($rootId));
        if (!root) return;

        const sections = Array.from(root.querySelectorAll('[data-dss-section]'));
        const tabs = Array.from(root.querySelectorAll('[data-dss-tab]'));
        const defaultSection = @json($defaultSection);

        const activateSection = (id, { updateHash = true } = {}) => {
            if (!sections.some((section) => section.dataset.dssSection === id)) return;
            sections.forEach((section) => { section.classList.toggle('d-none', section.dataset.dssSection !== id); });
            tabs.forEach((tab) => { tab.classList.toggle('active', tab.dataset.dssTab === id); });
            if (updateHash && window.location.hash !== `#${id}`) history.replaceState(null, '', `#${id}`);
            window.scrollTo(0, 0);
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', (event) => {
                event.preventDefault();
                activateSection(tab.dataset.dssTab);
            });
        });

        const sectionFromHash = () => {
            const id = window.location.hash.slice(1);
            if (!id) { activateSection(defaultSection, { updateHash: false }); return; }
            if (sections.some((section) => section.dataset.dssSection === id)) activateSection(id, { updateHash: false });
        };

        sectionFromHash();
        window.addEventListener('hashchange', sectionFromHash);
    })();
</script>
