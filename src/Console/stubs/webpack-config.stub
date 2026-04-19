const { glob } = require('glob');
const path = require('path');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

/**
 * Auto-discover entries from `resources/assets/`:
 *
 *   - apps/<name>/index.{ts,tsx,js,jsx}  → public/apps/<name>.js (+ .css via MiniCssExtract)
 *   - apps/<name>.{ts,tsx,js,jsx}        → public/apps/<name>.js
 *   - css/<name>.{scss,less,css}         → public/css/<name>.css
 *   - js/<name>.{ts,js}                  → public/js/<name>.js
 *
 * New app/style/script? Just drop a file in the right folder, no package.json edits needed.
 */
function autoEntries() {
  const entries = {};

  // React/TS apps — folder-based (for apps with multiple files)
  glob.sync('resources/assets/apps/*/index.{ts,tsx,js,jsx}').forEach((file) => {
    const name = path.basename(path.dirname(file));
    entries[`apps/${name}`] = `./${file}`;
  });

  // React/TS apps — single-file (for lightweight apps)
  glob.sync('resources/assets/apps/*.{ts,tsx,js,jsx}').forEach((file) => {
    const name = path.basename(file).replace(/\.(ts|tsx|js|jsx)$/, '');
    entries[`apps/${name}`] = `./${file}`;
  });

  // Standalone styles (CSS, SCSS, LESS)
  glob.sync('resources/assets/css/*.{scss,less,css}').forEach((file) => {
    const name = path.basename(file).replace(/\.(scss|less|css)$/, '');
    entries[`css/${name}`] = `./${file}`;
  });

  // Standalone scripts (JS, TS)
  glob.sync('resources/assets/js/*.{ts,js}').forEach((file) => {
    const name = path.basename(file).replace(/\.(ts|js)$/, '');
    entries[`js/${name}`] = `./${file}`;
  });

  return entries;
}

module.exports = {
  ...defaultConfig,
  entry: autoEntries(),
  output: {
    ...defaultConfig.output,
    path: path.resolve(__dirname, 'public'),
    filename: '[name].js',
  },
  plugins: [
    ...defaultConfig.plugins,
    // Strips the empty `.js` that webpack would generate for pure-CSS entries.
    new RemoveEmptyScriptsPlugin(),
  ],
};
