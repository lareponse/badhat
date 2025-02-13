# Structural Plan for IRSA Website Rewrite

## 1. **Goals and Objectives**
- **Performance**:
  - Achieve FCP < 2 seconds and LCP < 2.5 seconds.
  - Keep total page size < 2 MB.
  - Reduce the number of HTTP requests to < 30.
- **User Experience**:
  - Create a mobile-first, responsive design.
  - Ensure smooth navigation with CLS < 0.1.
- **Accessibility**:
  - Meet WCAG 2.1 Level AA compliance.
- **SEO**:
  - Optimize for Core Web Vitals.
  - Improve crawlability and indexing.
- **Maintainability**:
  - Use modular and reusable components.

---

## 2. **Core Pages**

### 2.1 Homepage
- **Purpose**: Provide a concise overview of IRSA’s mission, services, and key information.
- **Key Features**:
  - Lightweight hero section with minimal animations.
  - Prominent call-to-action buttons.
  - Quick links to services and contact information.

### 2.2 Services Page
- **Purpose**: Showcase the services offered by IRSA in detail.
- **Key Features**:
  - Section for each service with descriptive text and images.
  - Option to add downloadable PDFs or links to related content.

### 2.3 Contact Page
- **Purpose**: Enable users to easily reach out.
- **Key Features**:
  - Contact form with validation.
  - Google Maps integration for location (loaded on-demand).
  - FAQ section for common inquiries.

### 2.4 About Page
- **Purpose**: Provide an overview of IRSA’s mission and team.
- **Key Features**:
  - Timeline or milestones section.
  - Team member profiles with photos and bios.

### 2.5 Blog/News Page
- **Purpose**: Share updates, articles, and news.
- **Key Features**:
  - Search and filtering options.
  - Paginated list of posts with summaries.

---

## 3. **Design and Layout**

### 3.1 Framework and Styling
- Use **Tailwind CSS** or custom CSS for lightweight and maintainable styling.
- Focus on a clean, minimalistic design.
- Implement a mobile-first, responsive layout.

### 3.2 Navigation
- Sticky header with links to key pages.
- Mobile menu with collapsible sections.
- Breadcrumbs for secondary pages.

### 3.3 Footer
- Key links (e.g., Terms, Privacy, Contact).
- Social media icons.
- Small description of IRSA’s mission.

---

## 4. **Technology Stack**

### 4.1 Frontend
- **Languages**: HTML5, CSS3, JavaScript (Vanilla or Alpine.js).
- **Styling**: Tailwind CSS or custom lightweight CSS.
- **Assets**: Self-hosted fonts and optimized images (WebP format).

### 4.2 Backend
- **Language**: PHP (for simplicity) or Node.js.
- **Framework**: None or lightweight framework to minimize complexity.
- **Database**: MySQL or PostgreSQL for structured data.

### 4.3 Hosting and Deployment
- Use a modern CDN (e.g., Cloudflare) for asset delivery.
- Enable HTTP/2 or HTTP/3 for faster resource loading.
- Employ automated deployment pipelines.

---

## 5. **Performance Optimization**

### 5.1 Asset Optimization
- Compress and serve images in WebP format.
- Minify and combine CSS/JS files.
- Remove unused CSS/JS (e.g., tools like PurgeCSS).

### 5.2 Lazy Loading
- Images, videos, and maps should be lazy-loaded.

### 5.3 Caching
- Set proper caching headers for static assets.
- Use server-side caching (e.g., Redis).

---

## 6. **Accessibility Compliance**
- Use semantic HTML for all components.
- Ensure sufficient color contrast ratios.
- Add `alt` attributes to all images.
- Make navigation keyboard-accessible.

---

## 7. **Testing and Deployment**

### 7.1 Testing
- Use Lighthouse for performance, accessibility, and SEO audits.
- Test across modern browsers and devices (mobile, tablet, desktop).
- Conduct manual usability testing.

### 7.2 Deployment
- Use a staging environment for testing changes.
- Monitor performance post-launch and make iterative improvements.

---

## 8. **Timeline**

### Phase 1: Planning and Audit
- Duration: 2 weeks.
- Tasks:
  - Audit existing content.
  - Define performance and user experience goals.

### Phase 2: Development
- Duration: 6 weeks.
- Tasks:
  - Build core pages and implement the design.
  - Optimize performance and test iteratively.

### Phase 3: Testing and Launch
- Duration: 2 weeks.
- Tasks:
  - Finalize testing on staging.
  - Deploy to production and monitor post-launch performance.
