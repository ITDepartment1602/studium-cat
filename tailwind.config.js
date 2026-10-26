const fg = require('fast-glob');
const fs = require('fs');

// Tailwind's internal globber fails over UNC network shares (\\HOST\share):
// it mangles the path to backslashes and detects zero classes. On a normal
// local path (the server's C:\...) globbing works fine and enables --watch.
// So: use normal globs on local paths, and a raw-content fallback over UNC.
const dirFwd = __dirname.split('\\').join('/');
const isUNC  = __dirname.startsWith('\\\\') || __dirname.startsWith('//');

const content = isUNC
  ? fg.sync('admin/**/*.php', { cwd: dirFwd }).map((rel) => ({
      raw: fs.readFileSync(dirFwd + '/' + rel, 'utf8'),
      extension: 'php',
    }))
  : ['./admin/**/*.php'];

/** @type {import('tailwindcss').Config} */
module.exports = {
  content,

  // `tw-` prefix so utilities never collide with the Bootstrap 5 classes still
  // loaded on several admin pages (e.g. Bootstrap `.p-4` vs Tailwind `.p-4`).
  prefix: 'tw-',

  // Preflight (Tailwind's base reset) is disabled for the same reason — it
  // would reset Bootstrap/existing styling and change the current look.
  corePlugins: {
    preflight: false,
  },

  theme: {
    extend: {
      colors: {
        'ap-primary': '#007CBF',
        'ap-accent':  '#0D9488',
        'ap-text':    '#0f172a',
        'ap-muted':   '#64748b',
        'ap-bg':      '#f1f5f9',
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
    },
  },

  plugins: [],
};
