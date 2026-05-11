const clockElement = document.getElementById('attendance-clock');

if (clockElement) {
    const isFixed = clockElement.dataset.isFixed === 'true';

    if (!isFixed) {
        const updateClock = () => {
            const now = new Date();

            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            clockElement.textContent = `${hours}:${minutes}`;
        };

        updateClock();

        setInterval(updateClock, 1000);
    }
}