<!DOCTYPE>
<html>
    <head>
        <title>Reproductor</title>
        <meta charset="utf-8" />
    </head>
    <body>
        <div>
            <button type="button" id="play">Play</button>
            <button type="button" id="pause">Pause</button>
            <button type="button" id="stop">Stop</button>
        </div>
        <div>
            <input type="number" id="number" value="10" />
        </div>
        <div>
            <textarea id="text" cols="4" rows="5"></textarea>
        </div>
    </body>
    <script>
        var play = document.getElementById('play');
        var stop = document.getElementById('stop');
        var pause = document.getElementById('pause');
        var number = document.getElementById('number');
        var playing = true;
        var currentIndex = 0;
        var processes = [];
        play.onclick = function () {
            playing = true;
            for (var i = currentIndex; i < number.value; i++)
                processes.push(window.setTimeout(setText, (1000 * (i - currentIndex)), i));
        };
        stop.onclick = function () {
            playing = false;
            clearProcesses();
            currentIndex = 0;
        };
        pause.onclick = function () {
            playing = false;
            clearProcesses();
        };
        function setText (i) {
            currentIndex = i;
            if (playing == true)
            {
                var text = document.getElementById('text');
                text.value = i + 1;
            }
        }
        function clearProcesses()
        {
            for (var j = 0; j < processes.length; j++)
                    clearTimeout(processes[j]);
        }
    </script>
</html>