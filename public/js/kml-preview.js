(function () {
    'use strict';

    window.fitKmlPreview = function (map, layer) {
        var source = layer.getSource();

        function fitFootprint() {
            // A previous preview may finish loading after another is opened.
            if (map.getLayers().getArray().indexOf(layer) === -1) {
                source.un('change', fitFootprint);
                return;
            }

            var extent = source.getExtent();
            if (!source.getFeatures().length || !extent.every(Number.isFinite)) {
                return;
            }

            map.updateSize();
            var size = map.getSize();
            if (!size || size[0] <= 0 || size[1] <= 0) {
                return;
            }

            map.getView().fit(extent, {
                size: size,
                padding: [32, 32, 32, 32],
                maxZoom: 22
            });
            source.un('change', fitFootprint);
        }

        source.on('change', fitFootprint);
        fitFootprint();
    };
}());
