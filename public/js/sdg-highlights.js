(() => {
    const normalize = (value) => value.normalize('NFKC').toLocaleLowerCase().trim();

    document.querySelectorAll('[data-sdg-highlights]').forEach((root) => {
        const form = root.querySelector('[data-sdg-filters]');
        const search = form.querySelector('[data-sdg-search]');
        const goal = form.querySelector('[data-sdg-goal]');
        const count = root.querySelector('[data-sdg-count]');
        const empty = root.querySelector('[data-sdg-empty]');
        const cards = [...root.querySelectorAll('[data-sdg-card]')].map((element) => {
            const titles = JSON.parse(element.dataset.sdgActivities);
            return {
                element,
                goal: element.dataset.sdgGoal,
                text: normalize(`${element.dataset.sdgGoal} ${element.dataset.sdgName}`),
                titles,
                searchableTitles: titles.map(normalize),
                count: element.querySelector('[data-sdg-activity-count]'),
                preview: element.querySelector('[data-sdg-activity-titles]'),
            };
        });

        const filter = () => {
            const query = normalize(search.value);
            let visible = 0;
            cards.forEach((card) => {
                const matchesGoalText = card.text.includes(query);
                const titles = matchesGoalText
                    ? card.titles
                    : card.titles.filter((title, index) => card.searchableTitles[index].includes(query));
                const matches = (!goal.value || goal.value === card.goal) && titles.length > 0;
                card.element.hidden = !matches;
                if (!matches) return;
                visible += 1;
                card.count.textContent = `${titles.length} ${titles.length === 1 ? 'activity' : 'activities'}`;
                card.preview.textContent = titles.slice(0, 3).join(' · ');
            });
            count.textContent = `${visible} ${visible === 1 ? 'SDG' : 'SDGs'} found`;
            empty.hidden = visible > 0;
            empty.textContent = cards.length ? 'No SDGs match your filters.' : 'No SDG-tagged activities yet.';
        };

        form.addEventListener('submit', (event) => event.preventDefault());
        search.addEventListener('input', filter);
        goal.addEventListener('change', filter);
        form.addEventListener('reset', (event) => {
            event.preventDefault();
            search.value = '';
            goal.value = '';
            filter();
        });
        form.hidden = false;
        filter();
    });
})();
