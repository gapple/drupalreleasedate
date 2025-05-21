
var Timing = (function () {
    var timings = {};

    var timingObject = {
        /**
         * Start a timer with the provided key.
         *
         * If a timer with the provided key already exists, it is overwritten.
         *
         * @param key
         */
        start: function (key) {
            timings[key] = {
                start: new Date(),
                elapsed: 0
            };
        },
        /**
         * End the timer with the provided key, if it exists and is still running.
         *
         * @param key
         */
        stop: function (key) {
            if (typeof timings[key] === 'undefined' || typeof timings[key].start === 'undefined') {
                return;
            }

            timings[key].elapsed += (new Date()).getTime() - timings[key].start.getTime();
            delete timings[key].start;
        },
        /**
         * Continue a timer with the provided key.
         *
         * If the timer is still running, this has no effect.
         *
         * @param key
         */
        continue: function (key) {
            if (typeof timings[key] === 'undefined' || typeof timings[key].start !== 'undefined') {
                return;
            }
            timings[key].start = new Date();
        },
        /**
         * Get the elapsed time of the timer with the provided key.
         *
         * @param key
         *
         * @returns {number|undefined}
         */
        get: function (key) {
            var end;
            if (typeof timings[key] === 'undefined') {
                return;
            }

            // Timer is still running.
            if (typeof timings[key].start !== 'undefined') {
                return timings[key].elapsed + (new Date()).getTime() - timings[key].start.getTime();
            }

            return timings[key].elapsed;
        },
        /**
         * Get the elapsed time of all stored timers.
         *
         * @returns {}
         */
        getAll: function () {
            var timingDiffs = {};
            for (var key in timings) {
                timingDiffs[key] = timingObject.get(key);
            }
            return timingDiffs;
        }
    };

    return timingObject;
})();
