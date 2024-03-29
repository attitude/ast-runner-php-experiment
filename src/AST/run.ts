import { parse } from '@typescript-eslint/typescript-estree'
import chalk from 'chalk'
import { program } from 'commander'
import glob from 'fast-glob'
import fs from 'fs-extra'
import micromatch from 'micromatch'
import watch from 'node-watch'
import { dirname, resolve } from 'path'
import { cwd, exit } from 'process'
import promptly from 'promptly'
import ts from 'typescript'
import { log, setVerbose } from './logger.js'
import { NestedCodeLines } from './Types.js'

const filesPattern = '**/*.{ts,tsx}'

program
  .name('ast-runner')
  .description('CLI too to write Typescript to AST')
  .version('0.1.0')

program
  .command('watch')
  .argument('<string>', 'dir to watch')
  .option('-o, --outDir', 'output directory', './temp')
  .option('-e, --emptyOutDir', 'empty output directory', false)
  .option('-r, --recursive', 'watch recursively', true)
  .option('-v, --verbose', 'verbose output', false)
  .action(async (path: string, { recursive, verbose, outDir, emptyOutDir }: {
    emptyOutDir: boolean,
    outDir: string,
    recursive: boolean,
    verbose: boolean,
  }) => {
    const inDirPath = resolve(cwd(), path)
    const outDirPath = resolve(cwd(), outDir)

    if (verbose) {
      setVerbose(true)
    }

    log(chalk.yellow('cwd: ') + cwd())
    log(chalk.yellow('input: ') + inDirPath)
    log(chalk.yellow('output: ') + outDirPath)
    log(chalk.yellow('emptyOutDir: ') + emptyOutDir)
    log(chalk.yellow('recursive: ') + recursive)
    log(chalk.yellow('verbose: ') + verbose)

    if (!fs.existsSync(inDirPath)) {
      console.error(chalk.red(`Path does not exist: ${inDirPath}`))
      exit(1)
    }

    if (emptyOutDir) {
      fs.emptyDirSync(outDirPath)
    }

    if (!fs.existsSync(outDirPath)) {
      console.error(chalk.red(`Path does not exist: ${outDirPath}`))

      if (emptyOutDir || await promptly.confirm('Would you like to create the dir?')) {
        fs.mkdirSync(outDirPath, { recursive: true })
      } else {
        exit(1)
      }
    } else if (fs.lstatSync(outDirPath).isFile()) {
      console.error(chalk.red(`Output directory is a file: ${outDirPath}`) + '\nUse ' + chalk.yellow('-o <outDir>') + ' to output to a different path instead.')
      exit(1);
    }

    const globPattern = `${inDirPath}/${filesPattern}`

    console.log(`${chalk.green('Started to watch:')} ${globPattern}`)
    console.log(`${chalk.green('Output will go to:')} ${outDirPath}`)

    glob.sync(globPattern).forEach(file => {
      log.open('initial')(parseTsx(file, inDirPath, outDirPath)).close()
    })

    watch(inDirPath, {
      recursive,
      filter: file => micromatch.isMatch(file, globPattern)
    }, function (event, file) {
      log.open(event)(parseTsx(file, inDirPath, outDirPath)).close()
    })
  })

program.parse()

function outFilePath(file: string, inDirPath: string, outDirPath: string) {
  if (file.indexOf(inDirPath) === 0) {
    return file.replace(inDirPath, outDirPath);
  } else {
    console.error(chalk.red(`Input dir is not present in file path.`), {
      file,
      inDirPath,
    })
    exit(1);
  }
}

function expectEmptyObject(rest: Record<string | number | symbol, never>, shouldExit: boolean = true) {
  if (Object.keys(rest).length > 0) {

    if (shouldExit) {
      throw new Error("Expecting empty object");
    } else {
      console.warn(chalk.yellow('Expecting empty object'), rest);
    }
  }
}

function parseTsx(file: string, inDirPath: string, outDirPath: string) {
  const outputBase = outFilePath(file, inDirPath, outDirPath);

  if (fs.existsSync(file)) {
    const outputBaseDirPath = dirname(outputBase)

    if (!fs.existsSync(outputBaseDirPath)) {
      fs.mkdirSync(outputBaseDirPath, { recursive: true });
    }

    const content = fs.readFileSync(file).toString()
    // const { diagnostics, outputText, sourceMapText, ...transpilationRest } = ts.transpileModule(content, {
    //   compilerOptions: {
    //     target: ts.ScriptTarget.ESNext,
    //     jsx: ts.JsxEmit.ReactJSX,
    //   }
    // })

    // if (diagnostics && diagnostics.length > 0) {
    //   log(chalk.yellow('Diagnostics'), diagnostics);
    // }

    // expectEmptyObject(transpilationRest, false);

    // fs.writeFileSync(outputBase + '.js', outputText);

    // if (sourceMapText) {
    //   fs.writeFileSync(outputBase + '.js.map', sourceMapText);
    // } else {
    //   unlinkSyncSafe(outputBase + '.js.map');
    // }

    const ast = parse(content, {
      loc: true,
      tokens: true,
      jsx: true,
    })

    if (ast) {
      fs.writeFileSync(outputBase + '.ast.json', JSON.stringify(ast, null, 2));
    } else {
      unlinkSyncSafe(outputBase + '.ast.json');
    }

    return chalk.green('Transpiled: ') + file.replace(cwd(), '') + chalk.red(' > ') + outputBase.replace(cwd(), '');
  } else {
    unlinkSyncSafe(outputBase + '.js');
    unlinkSyncSafe(outputBase + '.js.map');
    unlinkSyncSafe(outputBase + '.ast.json');

    return chalk.yellow('Unlinked: ') + file.replace(cwd(), '') + chalk.red(' > ') + outputBase.replace(cwd(), '');
  }
}

function unlinkSyncSafe(file: string) {
  if (fs.existsSync(file)) {
    fs.unlinkSync(file)
  }
}

function concatNestedCodeLines(lines: NestedCodeLines, depth: number = 0): string {
  const indentation = '\t'.repeat(depth)

  return lines.map(line => {
    return Array.isArray(line)
      ? concatNestedCodeLines(line, depth + 1)
      : `${indentation}${line}`
  }).join('\n')
}
