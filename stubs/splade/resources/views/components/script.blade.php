<x-splade-script>
    function dark(toggle=false){
        let dark = localStorage.getItem('dark');
        if(dark === undefined || dark === null){
            dark = 1;
            localStorage.setItem('dark', dark);
        }
        if(toggle){
            dark == 1 ? dark = 0 : dark = 1;
            localStorage.setItem('dark', dark);
        }

        if (typeof document !== undefined) {
            document.body.classList[dark == 1 ? "add" : "remove"]("dark-scrollbars");
            document.documentElement.classList[dark == 1 ? "add" : "remove"]("dark");
        }
    }

    function dir(toggle=false){
        let dir = localStorage.getItem('dir');
        let htmlEl = document.querySelector("html");

        if(dir === undefined || dir === null){
            dir = 'ltr';
            localStorage.setItem('dir', 'ltr');
        }
        if(toggle){
            dir == 'ltr' ? dir = 'rtl' : dir = 'ltr';
            localStorage.setItem('dir', dir);
        }
        if (typeof document !== undefined) {
            dir === 'rtl' ? htmlEl.setAttribute("dir", "rtl")  : htmlEl.setAttribute("dir", "ltr");
        }
    }
    dark();
    dir();

    $splade.on('dark', function() {
        dark(true);
    });

    $splade.on('dir', function() {
        dir(true);
    });
</x-splade-script>
