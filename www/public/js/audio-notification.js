/**
 * Generador de sonidos de notificación usando Web Audio API
 * Para usar cuando no hay archivo de audio disponible
 */

class AudioNotification {
    constructor() {
        this.audioContext = null;
        this.init();
    }
    
    init() {
        try {
            // Crear contexto de audio
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {
            console.warn('Web Audio API no disponible:', e);
        }
    }
    
    // Generar tono de notificación
    playNotificationSound(frequency = 800, duration = 200) {
        if (!this.audioContext) return;
        
        try {
            // Crear oscilador
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            // Configurar oscilador
            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            // Configurar frecuencia y tipo
            oscillator.frequency.setValueAtTime(frequency, this.audioContext.currentTime);
            oscillator.type = 'sine';
            
            // Configurar volumen con envelope
            gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, this.audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + duration / 1000);
            
            // Reproducir
            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + duration / 1000);
            
        } catch (e) {
            console.warn('Error generando sonido:', e);
        }
    }
    
    // Sonido de éxito (doble tono)
    playSuccessSound() {
        this.playNotificationSound(800, 150);
        setTimeout(() => {
            this.playNotificationSound(1000, 150);
        }, 200);
    }
    
    // Sonido de error
    playErrorSound() {
        this.playNotificationSound(400, 300);
    }
    
    // Sonido de alerta
    playAlertSound() {
        this.playNotificationSound(600, 100);
        setTimeout(() => {
            this.playNotificationSound(800, 100);
        }, 150);
        setTimeout(() => {
            this.playNotificationSound(600, 100);
        }, 300);
    }
    
    // Sonido de RFID detectado (como en el legacy)
    playRFIDSound() {
        this.playSuccessSound();
    }
}

// Crear instancia global
window.audioNotifier = new AudioNotification(); 