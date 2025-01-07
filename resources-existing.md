# Libraries Used in IRSA Website

## CSS Libraries

1. **Font Awesome**
   - File: [font-awesome.min.css](http://www.irsa.be/media/gantry5/assets/css/font-awesome.min.css)
   - Purpose: Icon library for web projects.

2. **Bootstrap**
   - File: [bootstrap-gantry.css](http://www.irsa.be/media/gantry5/assets/css/bootstrap-gantry.css)
   - Purpose: CSS framework for responsive design and layout.

3. **UIkit**
   - File: [uikit.min.css](http://www.irsa.be/templates/g5_hydrogen/custom/uikit/css/uikit.min.css)
   - Purpose: Lightweight modular framework for modern web interfaces.

4. **IcoMoon**
   - File: [icomoon.css](http://www.irsa.be/media/jui/css/icomoon.css)
   - Purpose: Icon font library.

---

## JavaScript Libraries

1. **Google Maps API**
   - File: [Google Maps API](https://maps.googleapis.com/maps/api/js?v=3&key=AIzaSyB0qWVOvQvLp2Qe9-ZR9iZ8NrP5gZj7AU0&language=fr-FR&libraries=places)
   - Purpose: Interactive map functionalities and geolocation services.

2. **jQuery**
   - Files:
     - [jquery.min.js](http://www.irsa.be/media/jui/js/jquery.min.js)
     - [jquery-noconflict.js](http://www.irsa.be/media/jui/js/jquery-noconflict.js)
     - [jquery-migrate.min.js](http://www.irsa.be/media/jui/js/jquery-migrate.min.js)
   - Purpose: Simplifies DOM manipulation, event handling, and AJAX calls.

3. **MooTools**
   - Files:
     - [mootools-core.js](http://www.irsa.be/media/system/js/mootools-core.js)
     - [mootools-more.js](http://www.irsa.be/media/system/js/mootools-more.js)
   - Purpose: JavaScript framework for building advanced user interfaces.

4. **Bootstrap**
   - File: [bootstrap.min.js](http://www.irsa.be/media/jui/js/bootstrap.min.js)
   - Purpose: Provides responsive design elements and components.

5. **RokSprocket**
   - Files:
     - [rokmediaqueries.js](http://www.irsa.be/components/com_roksprocket/assets/js/rokmediaqueries.js)
     - [roksprocket.js](http://www.irsa.be/components/com_roksprocket/assets/js/roksprocket.js)
     - [features.js](http://www.irsa.be/components/com_roksprocket/layouts/features/assets/js/features.js)
     - [slideshow2.js](http://www.irsa.be/components/com_roksprocket/layouts/features/themes/slideshow2/slideshow2.js)
     - [basic.js](http://www.irsa.be/components/com_roksprocket/layouts/grids/themes/basic/basic.js)
   - Purpose: Joomla content module for advanced layouts and animations.

6. **UIkit**
   - File: [uikit.min.js](http://www.irsa.be/templates/g5_hydrogen/custom/uikit/js/uikit.min.js)
   - Purpose: JavaScript framework for modern web interfaces.

7. **Google Maps Plugin**
   - Files:
     - [googlemapsv3.js](http://www.irsa.be/media/plugin_googlemap3/site/googlemaps/googlemapsv3.js)
     - [modalbox1.3hackv3.js](http://www.irsa.be/media/plugin_googlemap3/site/moodalbox/js/modalbox1.3hackv3.js)
   - Purpose: Adds Google Maps functionalities to Joomla-based websites.

---

## Fonts

1. **Font Awesome**
   - File: [fontawesome-webfont.woff2](http://www.irsa.be/media/gantry5/assets/fonts/fontawesome-webfont.woff2?v=4.7.0)
   - Purpose: Icon library for modern web applications.

2. **Google Fonts**
   - File: [Arimo](https://fonts.googleapis.com/css?family=Arimo)
   - Purpose: Web-safe typography.



# Removal recommandations

## 1. Redundant or Outdated Libraries

### **MooTools**
- **Files**:
  - [mootools-core.js](http://www.irsa.be/media/system/js/mootools-core.js)
  - [mootools-more.js](http://www.irsa.be/media/system/js/mootools-more.js)
- **Reason**:
  - MooTools is outdated and no longer widely supported. Modern JavaScript libraries like jQuery or vanilla JavaScript can handle the same functionality more efficiently.
  - It adds unnecessary weight to the page.
- **Action**:
  - Audit and replace any functionality relying on MooTools with modern alternatives.

### **Google Maps Plugin Redundancy**
- **Files**:
  - [googlemapsv3.js](http://www.irsa.be/media/plugin_googlemap3/site/googlemaps/googlemapsv3.js)
  - [modalbox1.3hackv3.js](http://www.irsa.be/media/plugin_googlemap3/site/moodalbox/js/modalbox1.3hackv3.js)
- **Reason**:
  - Duplicate Google Maps-related scripts are loaded (e.g., [Google Maps API](https://maps.googleapis.com/maps/api/js)).
  - This causes redundancy and slower load times.
- **Action**:
  - Consolidate all map functionality into a single script using the official Google Maps API.

---

## 2. Unused or Excessive CSS

### **Font Awesome**
- **File**: [font-awesome.min.css](http://www.irsa.be/media/gantry5/assets/css/font-awesome.min.css)
- **Reason**:
  - If only a few icons are used, the entire library is unnecessary overhead.
  - Using inline SVGs or a subset of Font Awesome can replace this heavy file.
- **Action**:
  - Subset the icons required or replace them with inline SVGs.

### **Bootstrap**
- **File**: [bootstrap-gantry.css](http://www.irsa.be/media/gantry5/assets/css/bootstrap-gantry.css)
- **Reason**:
  - If the site uses UIkit for its framework, Bootstrap might be redundant.
- **Action**:
  - Audit the CSS to determine whether Bootstrap can be removed entirely.

### **IcoMoon**
- **File**: [icomoon.css](http://www.irsa.be/media/jui/css/icomoon.css)
- **Reason**:
  - This may be redundant if Font Awesome or inline SVGs are already being used for icons.
- **Action**:
  - Replace or consolidate icon usage.

---

## 3. Duplicate and Unnecessary Fonts

### **Google Fonts Arimo**
- **Files**:
  - [Arimo (HTTP)](http://fonts.gstatic.com/s/arimo/v29/P5sfzZCDf9_T_3cV7NCUECyoxNk37cxcABrB.woff2)
  - [Arimo (HTTPS)](https://fonts.gstatic.com/s/arimo/v29/P5sfzZCDf9_T_3cV7NCUECyoxNk37cxcABrB.woff2)
- **Reason**:
  - Loading fonts over both HTTP and HTTPS creates redundancy and potential security issues.
- **Action**:
  - Use a single, HTTPS-secure source for the font.

---

## 4. Consolidate or Remove Redundant JavaScript

### **jQuery Duplicates**
- **Files**:
  - [jquery.min.js](http://www.irsa.be/media/jui/js/jquery.min.js)
  - [jquery-noconflict.js](http://www.irsa.be/media/jui/js/jquery-noconflict.js)
  - [jquery-migrate.min.js](http://www.irsa.be/media/jui/js/jquery-migrate.min.js)
- **Reason**:
  - Multiple versions of jQuery-related scripts increase load time and may cause conflicts.
- **Action**:
  - Use a single, latest version of jQuery and remove `jquery-noconflict.js` and `jquery-migrate.min.js` unless necessary.

---

## 5. Heavy or Redundant Images

### **Cached Images**
- **Files**:
  - [cache/mod_roksprocket](e.g., `3b0989e261bbe0ea3a4ad82e59b31282_630_1680.png`)
- **Reason**:
  - These images may be outdated or generated unnecessarily by extensions like Roksprocket.
- **Action**:
  - Remove unused images or replace them with optimized ones.

### **Large Gallery Images**
- **Files**:
  - [Gallery Image 1](http://www.irsa.be/media/rokgallery/f/f2ed79a5-0880-41ad-9276-022f42973a4a/68ddd7cb-3fdb-4616-d382-ec220aeb066f.jpg)
  - [Gallery Image 2](http://www.irsa.be/media/rokgallery/f/f15db25f-e729-4312-dda2-8d58fa347d92/96e9323d-256e-4b85-b5aa-0f2438ad75cf.jpg)
- **Reason**:
  - Large images significantly increase page load time.
- **Action**:
  - Compress these images and serve them in next-gen formats (e.g., WebP).

---

## 6. Unused RokSprocket Files
- **Files**:
  - [rokmediaqueries.js](http://www.irsa.be/components/com_roksprocket/assets/js/rokmediaqueries.js)
  - [roksprocket.js](http://www.irsa.be/components/com_roksprocket/assets/js/roksprocket.js)
- **Reason**:
  - If RokSprocket is not actively used for content, its files add unnecessary weight.
- **Action**:
  - Remove these files if they are not critical to the site’s functionality.

---

By removing or replacing these redundant and outdated resources, the website can improve load times, simplify maintenance, and reduce bandwidth usage.
