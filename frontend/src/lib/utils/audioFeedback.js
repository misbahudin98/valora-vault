// audioFeedback.js - Tactile UI Auditory Feedback Engine based on W3C Web Audio API
// Zero external asset payload (0 KB HTTP), procedural synthesis, ultra-low latency, and accessible opt-in.

class TactileAudioEngine {
  constructor() {
    this.ctx = null;
    // Default muted for user respect; user can toggle and state persists in localStorage
    this.muted = typeof window !== 'undefined' ? localStorage.getItem('tactile_audio_muted') === 'true' : false;
  }

  // Ensure AudioContext is initialized after user gesture (respecting browser autoplay policy)
  init() {
    if (typeof window === 'undefined') return;
    if (!this.ctx) {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (AudioCtx) {
        this.ctx = new AudioCtx();
      }
    }
    if (this.ctx && this.ctx.state === 'suspended') {
      this.ctx.resume().catch(() => {});
    }
  }

  // Soft tactile tap / click (Apple-style subtle micro tick, ~40ms)
  playTap() {
    if (this.muted) return;
    this.init();
    if (!this.ctx || this.ctx.state !== 'running') return;

    try {
      const now = this.ctx.currentTime;
      const osc = this.ctx.createOscillator();
      const gain = this.ctx.createGain();

      osc.type = 'sine';
      // Fast exponential pitch drop creates a clean physical "tick" feel
      osc.frequency.setValueAtTime(360, now);
      osc.frequency.exponentialRampToValueAtTime(110, now + 0.04);

      // Low volume level prevents ear fatigue
      gain.gain.setValueAtTime(0.06, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);

      osc.connect(gain);
      gain.connect(this.ctx.destination);

      osc.start(now);
      osc.stop(now + 0.04);
    } catch (e) {
      // Graceful fallback if Web Audio is restricted
    }
  }

  // Soft glass pop / expand sound (~70ms) for modal, accordion, or navigation
  playPop() {
    if (this.muted) return;
    this.init();
    if (!this.ctx || this.ctx.state !== 'running') return;

    try {
      const now = this.ctx.currentTime;
      const osc = this.ctx.createOscillator();
      const gain = this.ctx.createGain();

      osc.type = 'sine';
      osc.frequency.setValueAtTime(240, now);
      osc.frequency.exponentialRampToValueAtTime(480, now + 0.07);

      gain.gain.setValueAtTime(0.05, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.07);

      osc.connect(gain);
      gain.connect(this.ctx.destination);

      osc.start(now);
      osc.stop(now + 0.07);
    } catch (e) {
      // Graceful fallback
    }
  }

  // Success chime (harmonic ascending major triad: C5 -> E5 -> G5, ~180ms total)
  playSuccess() {
    if (this.muted) return;
    this.init();
    if (!this.ctx || this.ctx.state !== 'running') return;

    try {
      const baseTime = this.ctx.currentTime;
      const notes = [523.25, 659.25, 783.99]; // C5, E5, G5

      notes.forEach((freq, idx) => {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        const startTime = baseTime + (idx * 0.05);
        const duration = 0.12;

        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, startTime);

        gain.gain.setValueAtTime(0.04, startTime);
        gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

        osc.connect(gain);
        gain.connect(this.ctx.destination);

        osc.start(startTime);
        osc.stop(startTime + duration);
      });
    } catch (e) {
      // Graceful fallback
    }
  }

  // Switch / Toggle sound (~50ms)
  playToggle(isOn = true) {
    if (this.muted) return;
    this.init();
    if (!this.ctx || this.ctx.state !== 'running') return;

    try {
      const now = this.ctx.currentTime;
      const osc = this.ctx.createOscillator();
      const gain = this.ctx.createGain();

      osc.type = 'sine';
      const startFreq = isOn ? 300 : 420;
      const endFreq = isOn ? 520 : 260;

      osc.frequency.setValueAtTime(startFreq, now);
      osc.frequency.exponentialRampToValueAtTime(endFreq, now + 0.05);

      gain.gain.setValueAtTime(0.05, now);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05);

      osc.connect(gain);
      gain.connect(this.ctx.destination);

      osc.start(now);
      osc.stop(now + 0.05);
    } catch (e) {
      // Graceful fallback
    }
  }

  toggleMute() {
    this.muted = !this.muted;
    if (typeof window !== 'undefined') {
      localStorage.setItem('tactile_audio_muted', this.muted ? 'true' : 'false');
    }
    if (!this.muted) {
      this.playTap();
    }
    return this.muted;
  }
}

export const tactileAudio = new TactileAudioEngine();
