document.addEventListener('DOMContentLoaded', () => {
    const hoursMinutesElement = document.getElementById('clock-hm');
    const secondsElement = document.getElementById('clock-s');
    
    if (hoursMinutesElement && secondsElement) {
        setInterval(() => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            hoursMinutesElement.textContent = `${hours}:${minutes}`;
            secondsElement.textContent = seconds;
        }, 1000);
    }
});