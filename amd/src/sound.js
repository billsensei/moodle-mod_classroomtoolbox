// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Sounds for the tools, made with the Web Audio API (no sound files). One on/off preference is remembered in the
 * browser and shared by every tool, so a teacher can mute everything once.
 *
 * @module     mod_classroomtoolbox/sound
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const KEY = 'mod_classroomtoolbox_sound';
let context = null;

/**
 * Whether sound is switched on (the default).
 *
 * @returns {boolean}
 */
export const isEnabled = () => {
    try {
        return window.localStorage.getItem(KEY) !== 'off';
    } catch (e) {
        return true;
    }
};

/**
 * Remember the sound preference.
 *
 * @param {boolean} on Whether sound is wanted.
 */
export const setEnabled = (on) => {
    try {
        window.localStorage.setItem(KEY, on ? 'on' : 'off');
    } catch (e) {
        // Storage can be blocked; the preference then lasts only for this page.
    }
};

/**
 * Browsers only allow sound after a click or key press, so call this from one before playing anything.
 */
export const prepare = () => {
    try {
        if (!context) {
            const Context = window.AudioContext || window.webkitAudioContext;
            context = Context ? new Context() : null;
        }
        if (context && context.state === 'suspended') {
            context.resume();
        }
    } catch (e) {
        context = null;
    }
};

/**
 * Play one short tone: a note with a quick rise and fall.
 *
 * @param {number} frequency Pitch in Hz.
 * @param {number} delay Seconds from now.
 * @param {number} length Seconds the note lasts.
 * @param {number} volume Peak volume from 0 to 1.
 * @param {string} type Oscillator type.
 */
const note = (frequency, delay, length, volume, type) => {
    const start = context.currentTime + delay;
    const osc = context.createOscillator();
    const gain = context.createGain();
    osc.type = type;
    osc.frequency.value = frequency;
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
    osc.connect(gain);
    gain.connect(context.destination);
    osc.start(start);
    osc.stop(start + length + 0.02);
};

/**
 * Run a sound if sound is on and the browser allows it; never let a sound problem break the tool.
 *
 * @param {Function} play Function that plays notes.
 * @param {boolean} force Play even when sound is switched off (for a "try this sound" button the teacher pressed).
 */
const guarded = (play, force = false) => {
    if ((!force && !isEnabled()) || !context) {
        return;
    }
    try {
        play();
    } catch (e) {
        // Sound is a nicety.
    }
};

/**
 * A soft click, like a wheel flapper passing a peg.
 */
export const tick = () => guarded(() => note(1500, 0, 0.035, 0.1, 'square'));

/**
 * A short rising chime for a picked name.
 */
export const chime = () => guarded(() => {
    [523.25, 659.25, 783.99, 1046.5].forEach((frequency, i) => {
        note(frequency, i * 0.11, i === 3 ? 0.7 : 0.3, 0.22, 'triangle');
    });
});

/**
 * Play one swept tone: a single oscillator whose pitch glides, with a quick rise and fall in volume.
 *
 * @param {number} delay Seconds from now.
 * @param {number} from Starting pitch in Hz.
 * @param {number} to Ending pitch in Hz.
 * @param {number} length Seconds the tone lasts.
 * @param {number} volume Peak volume from 0 to 1.
 * @param {string} type Oscillator type.
 */
const glide = (delay, from, to, length, volume, type) => {
    const start = context.currentTime + delay;
    const osc = context.createOscillator();
    const gain = context.createGain();
    osc.type = type;
    osc.frequency.setValueAtTime(from, start);
    osc.frequency.linearRampToValueAtTime(to, start + length);
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + 0.01);
    gain.gain.setValueAtTime(volume, start + length * 0.7);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
    osc.connect(gain);
    gain.connect(context.destination);
    osc.start(start);
    osc.stop(start + length + 0.02);
};

/**
 * One blast of a horn or siren: two slightly detuned square waves (which give the rough, beating edge) whose pitch
 * follows the given points, under one volume envelope.
 *
 * @param {number} delay Seconds from now.
 * @param {number} length Seconds the blast lasts.
 * @param {number[][]} path Pitch points as [fraction of the length, Hz].
 * @param {number} attack Seconds to fade in.
 * @param {number} release Seconds to fade out.
 */
const horn = (delay, length, path, attack, release) => {
    const start = context.currentTime + delay;
    const gain = context.createGain();
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(0.48, start + attack);
    gain.gain.setValueAtTime(0.48, start + length - release);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
    gain.connect(context.destination);
    [0, 8].forEach((detune) => {
        const osc = context.createOscillator();
        osc.type = 'square';
        osc.frequency.setValueAtTime(path[0][1] + detune, start);
        path.slice(1).forEach(([at, hz]) => osc.frequency.linearRampToValueAtTime(hz + detune, start + length * at));
        osc.connect(gain);
        osc.start(start);
        osc.stop(start + length + 0.02);
    });
};

/** @type {number} How hard the quack is pushed into soft saturation (higher is louder and harsher). */
const QUACK_DRIVE = 6;

/**
 * One "quack", modelled on measurements of a real recorded quack (the macOS "Quack" alert, used only as a measuring
 * example): it lasts about 0.18 s; the voice is low and buzzy, about 145 Hz at the start, rising to about 180 Hz a
 * hundred milliseconds in and falling to about 140 Hz at the end; most of the sound sits between 1.4 and 2.0 kHz,
 * with weaker bands near 400 Hz and 2.4 kHz and almost nothing above 4 kHz; the volume rises in about 15 ms, holds,
 * then fades away.
 *
 * @param {number} delay Seconds from now.
 * @param {number} pitch The highest pitch of the voice in Hz (about 180).
 */
const quack = (delay, pitch) => {
    const start = context.currentTime + delay;
    const length = 0.185;

    // The volume: a quick rise, a hold, then a fade that ends in a cut.
    const out = context.createGain();
    out.gain.setValueAtTime(0.0001, start);
    out.gain.exponentialRampToValueAtTime(0.66, start + 0.018);
    out.gain.setValueAtTime(0.66, start + 0.06);
    out.gain.exponentialRampToValueAtTime(0.23, start + 0.15);
    out.gain.exponentialRampToValueAtTime(0.0001, start + length);
    // Soft saturation rounds off the spiky peaks of the buzzy voice, so the quack can be as loud as the other alarm
    // sounds; a low-pass filter then takes away the extra high notes this makes, as the real quack has almost none.
    const saturate = context.createWaveShaper();
    const curve = new Float32Array(513);
    curve.forEach((value, i, all) => {
        all[i] = Math.tanh(QUACK_DRIVE * ((i / 256) - 1));
    });
    saturate.curve = curve;
    const soften = context.createBiquadFilter();
    soften.type = 'lowpass';
    soften.frequency.value = 3800;
    soften.Q.value = 0.7;
    out.connect(saturate);
    saturate.connect(soften);
    const level = context.createGain();
    level.gain.value = 0.75;
    soften.connect(level);
    level.connect(context.destination);

    // The voice: a buzzy saw wave (a train of pulses, which is the growl) that rises and then falls in pitch.
    const voice = context.createOscillator();
    voice.type = 'sawtooth';
    voice.frequency.setValueAtTime(pitch * 0.77, start);
    voice.frequency.linearRampToValueAtTime(pitch, start + 0.115);
    voice.frequency.linearRampToValueAtTime(pitch * 0.75, start + 0.17);
    voice.start(start);
    voice.stop(start + length + 0.02);

    // The mouth: three bands of frequencies are let through, the middle one (about 1.6 kHz) the strongest.
    [[420, 3, 0.5], [850, 3, 1], [1600, 6, 3.4], [2400, 6, 2.2]].forEach(([hz, q, level]) => {
        const filter = context.createBiquadFilter();
        filter.type = 'bandpass';
        filter.frequency.value = hz;
        filter.Q.value = q;
        const mix = context.createGain();
        mix.gain.value = level;
        voice.connect(filter);
        filter.connect(mix);
        mix.connect(out);
    });
};

/** @type {Object<string, Function>} Each alarm sound, about three seconds long. */
const SOUNDS = {
    // A hazard siren, like the alarm in a movie factory when something dangerous has spilled: five rising whoops.
    siren: () => {
        for (let i = 0; i < 5; i++) {
            horn(i * 0.8, 0.66, [[0, 450], [1, 1100]], 0.03, 0.06);
        }
    },
    // Plain, steady beeping, like a digital watch or a microwave.
    beeps: () => {
        for (let i = 0; i < 8; i++) {
            glide(i * 0.36, 1000, 1000, 0.17, 0.85, 'square');
        }
    },
    // An electric alarm clock: bursts of four quick beeps, six times.
    clock: () => {
        for (let burst = 0; burst < 6; burst++) {
            for (let beep = 0; beep < 4; beep++) {
                glide(burst * 0.5 + beep * 0.11, 1800, 1800, 0.075, 0.8, 'square');
            }
        }
    },
    // A submarine dive klaxon: three long "ah-OOO-gah" blasts.
    klaxon: () => {
        for (let i = 0; i < 3; i++) {
            horn(i * 1.3, 1.2, [[0, 300], [0.4, 520], [0.6, 520], [1, 300]], 0.08, 0.15);
        }
    },
    // Science-fiction computer beeps: quick gliding "bee-boo" pairs, with a high blip on top.
    futuristic: () => {
        for (let i = 0; i < 8; i++) {
            glide(i * 0.4, 600, 1800, 0.14, 0.85, 'sine');
            glide(i * 0.4 + 0.16, 1800, 900, 0.14, 0.85, 'sine');
            glide(i * 0.4 + 0.32, 2600, 2600, 0.06, 0.4, 'triangle');
        }
    },
    // A duck: "quack, quack, quack-quack-quack", each a little lower than the last.
    duck: () => {
        [[0, 185], [0.5, 178], [1, 172], [1.35, 166], [1.7, 160], [2.05, 154]].forEach(([at, pitch]) => quack(at, pitch));
    },
};

/** @type {string[]} The ids of the alarm sounds a timer can use; the first one is the default. */
export const KINDS = Object.keys(SOUNDS);

/**
 * Play an alarm sound (for example when a timer ends).
 *
 * @param {string} kind One of KINDS; anything else plays the default.
 * @param {boolean} force Play even when sound is switched off (for a "try this sound" button).
 */
export const play = (kind, force = false) => guarded(SOUNDS[kind] || SOUNDS[KINDS[0]], force);
