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
 */
const guarded = (play) => {
    if (!isEnabled() || !context) {
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
 * One blast of a horn: a harsh tone whose pitch rises, holds and falls ("ah-OOO-gah"), like an old diving klaxon.
 *
 * Two slightly detuned square waves are layered, which gives the horn its rough, beating buzz.
 *
 * @param {number} delay Seconds from now.
 * @param {number} length Seconds the blast lasts.
 */
const blast = (delay, length) => {
    const start = context.currentTime + delay;
    const gain = context.createGain();
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(0.48, start + 0.08);
    gain.gain.setValueAtTime(0.48, start + length - 0.15);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
    gain.connect(context.destination);
    [0, 6].forEach((detune) => {
        const osc = context.createOscillator();
        osc.type = 'square';
        osc.frequency.setValueAtTime(300 + detune, start);
        osc.frequency.linearRampToValueAtTime(520 + detune, start + length * 0.4);
        osc.frequency.setValueAtTime(520 + detune, start + length * 0.6);
        osc.frequency.linearRampToValueAtTime(300 + detune, start + length);
        osc.connect(gain);
        osc.start(start);
        osc.stop(start + length + 0.02);
    });
};

/**
 * A submarine dive klaxon for a finished timer: three "aoo-gah" blasts, about 4 seconds in all.
 */
export const alarm = () => guarded(() => {
    for (let i = 0; i < 3; i++) {
        blast(i * 1.3, 1.2);
    }
});
