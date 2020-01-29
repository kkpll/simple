//画像の遅延ロード
document.addEventListener("DOMContentLoaded", function() {
  let lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
  if ("IntersectionObserver" in window) {
    let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          let lazyImage = entry.target;
          if (lazyImage.dataset.hasOwnProperty('src')) {
            lazyImage.src = lazyImage.dataset.src;
            lazyImage.dataset.src = '';
            delete lazyImage.dataset.src;
          }
          if (lazyImage.dataset.hasOwnProperty('srcset')) {
            lazyImage.srcset = lazyImage.dataset.srcset;
            lazyImage.dataset.srcset = '';
            delete lazyImage.dataset.srcset;
          }
          lazyImage.classList.remove("lazy");
          lazyImageObserver.unobserve(lazyImage);
        }
      });
    });
    lazyImages.forEach(function(lazyImage) {
      lazyImageObserver.observe(lazyImage);
    });
  }
});
