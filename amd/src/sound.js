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

/**
 * One "quack": a rough, nasal tone whose pitch rises a little and then falls, shaped by a band-pass filter.
 *
 * @param {number} delay Seconds from now.
 * @param {number} pitch Starting pitch in Hz.
 */
const quack = (delay, pitch) => {
    const start = context.currentTime + delay;
    const length = 0.26;
    const filter = context.createBiquadFilter();
    filter.type = 'bandpass';
    filter.frequency.value = 1100;
    filter.Q.value = 0.9;
    const gain = context.createGain();
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(0.47, start + 0.015);
    gain.gain.setValueAtTime(0.47, start + length * 0.55);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
    filter.connect(gain);
    gain.connect(context.destination);
    // Two saw waves a few tens of Hz apart beat against each other, which gives the growl of a real quack.
    [0, 55].forEach((detune) => {
        const osc = context.createOscillator();
        osc.type = 'square';
        osc.frequency.setValueAtTime(pitch + detune, start);
        osc.frequency.linearRampToValueAtTime(pitch * 1.35 + detune, start + 0.045);
        osc.frequency.linearRampToValueAtTime(pitch * 0.62 + detune, start + length);
        osc.connect(filter);
        osc.start(start);
        osc.stop(start + length + 0.02);
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
    // A duck: five quacks, a little different each time.
    duck: () => {
        [[0, 360], [0.5, 330], [1.25, 380], [1.75, 340], [2.25, 300]].forEach(([at, pitch]) => quack(at, pitch));
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
