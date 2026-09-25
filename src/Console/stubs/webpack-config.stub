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

/**
 * TypeScript declaration files carry no runtime code, but `*.ts` matches `*.d.ts` and the
 * entry name would keep the inner `.d` — `global.d.ts` became the entry `apps/global.d`
 * and emitted an empty `public/apps/global.d.js` next to its `.asset.php`.
 */
const IGNORE_DECLARATIONS = { ignore: '**/*.d.ts' };

/**
 * `@wordpress/scripts` ships loader rules for `.css` and `.scss`/`.sass` only, so a `.less` entry
 * found below failed with "You may need an appropriate loader". This rule reuses the loaders of
 * the scss rule (CSS extraction, css-loader, PostCSS) with `less-loader` in place of
 * `sass-loader`, so LESS output is minified and gets its `-rtl.css` like SCSS does.
 */
const sassRule = defaultConfig.module.rules.find(
  (rule) => rule.test instanceof RegExp && rule.test.test('.scss')
);
if (!sassRule) {
  throw new Error(
    'webpack.config.js: no .scss rule in the @wordpress/scripts config to build the .less rule from.'
  );
}
const lessRule = {
  test: /\.less$/,
  use: [
    ...sassRule.use.filter((loader) => !String(loader.loader ?? loader).includes('sass-loader')),
    {
      loader: require.resolve('less-loader'),
      options: { sourceMap: defaultConfig.mode !== 'production' },
    },
  ],
};

function autoEntries() {
  const entries = {};

  // React/TS apps — folder-based (for apps with multiple files)
  glob
    .sync('resources/assets/apps/*/index.{ts,tsx,js,jsx}', IGNORE_DECLARATIONS)
    .forEach((file) => {
      const name = path.basename(path.dirname(file));
      entries[`apps/${name}`] = `./${file}`;
    });

  // React/TS apps — single-file (for lightweight apps)
  glob.sync('resources/assets/apps/*.{ts,tsx,js,jsx}', IGNORE_DECLARATIONS).forEach((file) => {
    const name = path.basename(file).replace(/\.(ts|tsx|js|jsx)$/, '');
    entries[`apps/${name}`] = `./${file}`;
  });

  // Standalone styles (CSS, SCSS, LESS)
  glob.sync('resources/assets/css/*.{scss,less,css}').forEach((file) => {
    const name = path.basename(file).replace(/\.(scss|less|css)$/, '');
    entries[`css/${name}`] = `./${file}`;
  });

  // Standalone scripts (JS, TS)
  glob.sync('resources/assets/js/*.{ts,js}', IGNORE_DECLARATIONS).forEach((file) => {
    const name = path.basename(file).replace(/\.(ts|js)$/, '');
    entries[`js/${name}`] = `./${file}`;
  });

  return entries;
}

module.exports = {
  ...defaultConfig,
  entry: autoEntries(),
  module: {
    ...defaultConfig.module,
    rules: [...defaultConfig.module.rules, lessRule],
  },
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
