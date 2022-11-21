import chalk from 'chalk'
import util from 'util'

let verbose = false
const contexts: string[] = []

export function setVerbose(next: boolean) {
  verbose = next
}

function dir(value: any) {
  console.log(util.inspect(value, false, null, true))
}

export function log(...rest: any[]) {
  if (verbose) {
    rest.forEach(value => {
      if (typeof value === 'string') {
        console.log(value)
      } else {
        dir(value)
      }
    })
  }

  return log
}

log.open = function open(context: string) {
  contexts.push(context)
  console.log(`${chalk.red('>')} ${chalk.yellow(`${contexts.join(chalk.grey('.'))}`)}`)

  return log
}

log.close = function close() {
  contexts.pop()
  console.log(`${chalk.red('<')} ${chalk.yellow(`${contexts.join(chalk.grey('.'))}`)}`)

  return log
}
