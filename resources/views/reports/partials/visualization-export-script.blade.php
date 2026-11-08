<script>
(function () {
    var form = document.getElementById('visualization-pdf-form');

    if (! form) {
        return;
    }

    var button = form.querySelector('button[type="submit"]');
    var imageField = form.querySelector('input[name="chart_image"]');
    var busyLabel = @json(__('Preparing PDF…'));
    var idleLabel = button.innerHTML;
    var svgStyleProps = ['fill', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-dasharray', 'stroke-linejoin', 'opacity', 'paint-order',
        'font-family', 'font-size', 'font-weight', 'font-style', 'letter-spacing', 'text-anchor', 'dominant-baseline'];

    var onWhite = function (source, width, height) {
        var canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(source, 0, 0, width, height);

        return canvas.toDataURL('image/png');
    };

    var mapImage = function (svg) {
        var clone = svg.cloneNode(true);
        var sources = svg.querySelectorAll('*');
        var targets = clone.querySelectorAll('*');

        sources.forEach(function (element, index) {
            var computed = getComputedStyle(element);
            var style = svgStyleProps.map(function (prop) {
                return prop + ':' + computed.getPropertyValue(prop);
            }).join(';');
            targets[index].setAttribute('style', style);
        });

        var box = svg.viewBox.baseVal;
        var scale = 2;
        clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        clone.setAttribute('width', box.width * scale);
        clone.setAttribute('height', box.height * scale);

        return new Promise(function (resolve, reject) {
            var image = new Image();
            image.onload = function () { resolve(onWhite(image, box.width * scale, box.height * scale)); };
            image.onerror = reject;
            image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(clone));
        });
    };

    var captureImage = function () {
        var svg = document.querySelector('#visualization .me-map-svg');

        if (svg) {
            return mapImage(svg);
        }

        var canvas = document.getElementById('report-visual-chart');

        return Promise.resolve(canvas ? onWhite(canvas, canvas.width, canvas.height) : '');
    };

    form.addEventListener('submit', function (event) {
        if (form.dataset.ready === '1') {
            form.dataset.ready = '';

            return;
        }

        event.preventDefault();
        button.disabled = true;
        button.textContent = busyLabel;

        captureImage().catch(function () { return ''; }).then(function (dataUri) {
            imageField.value = dataUri;
            form.dataset.ready = '1';
            form.requestSubmit ? form.requestSubmit() : form.submit();
            setTimeout(function () {
                button.disabled = false;
                button.innerHTML = idleLabel;
            }, 1500);
        });
    });
})();
</script>
