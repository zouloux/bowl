#!/usr/bin/env node

const { FileFinder, File, Directory } = require( "@solid-js/files" );
const { askList, nicePrint, printLoaderLine, askInput, execAsync } = require( "@solid-js/cli" );
const path = require( "path" );
const Preferences = require('preferences')

// ----------------------------------------------------------------------------- UTILS

const version = require('./package.json').version
const printUsingVersion = () => nicePrint(`{d}Using Solid Chimera Starter {b/d}v${version}`);

const preferences = new Preferences('zouloux.create-solid-chimera-app', {}, {
	encrypt: true
})

async function getPHPVersion () {
	try {
		let phpVersion = await execAsync('php -v')
		return phpVersion.split("\n")[0].split(" ")[1].split('.').map( s => parseInt(s) )
	}
	catch (e) { return false }
}

async function getDockerIsRunning () {
	try {
		await execAsync(`docker ps`)
		return true
	}
	catch (e) { return false }
}

async function cliTask ( options ) {
	// console.log(options.command)
	const loader = printLoaderLine( options.title )
	try {
		await execAsync(options.command, 0, { // TODO : -v -> enable verbose logging
			cwd: process.cwd()
		})
	}
	catch (e) {
		if ( options.fallback && options.fallback(e) ) {
			loader( options.success )
			return
		}
		loader( options.error ?? options.title, 'error' )
		e && console.error( e )
		options.error && process.exit( options.code )
	}
	loader( options.success )
}

// Public scope, shared with install.js scripts
module.exports = {
	FileFinder, File, Directory,
	printLoaderLine, nicePrint, askList, askInput, execAsync,
	getPHPVersion, getDockerIsRunning,
	cliTask,
}

// ----------------------------------------------------------------------------- MAIN

let selectedBackend = false
// selectedBackend = 'wordpress'

;(async function ()
{
	// ------------------------------------------------------------------------- INIT

	printUsingVersion();

	if ( process.argv.length >= 3 && process.argv[2].toLowerCase() === 'clear-preferences' ) {
		preferences.clear()
		preferences.save()
		nicePrint(`{b/g}Preferences cleared`)
		process.exit();
	}

	// ------------------------------------------------------------------------- CHECK CWD

	// List files in current directory
	const currentFolderContent = await FileFinder.list('*')

	// Check that we are in an init git directory
	if ( !currentFolderContent.find( filePath => filePath === '.git' ) )
		nicePrint(`{b/r}Please init a git repository here to continue`, { code: 1 })

	// Ask confirm if we got some files (> 1 because of .git which is mandatory)
	if ( !selectedBackend && currentFolderContent.length > 1 ) {
		const someFiles = currentFolderContent.filter( (a, i) => i < 6 && a !== '.git' )
		nicePrint(`
			{b/o}Warning, current directory is not empty, some file may be overridden.
			Found elements : {d}${someFiles.join(', ')} ...
		`)
		const answer = await askList('Are you sure to continue ?', ['No', 'Yes'], { returnType: 'value' })
		if ( answer === 'No' ) process.exit(0)
	}

	// ------------------------------------------------------------------------- SELECT BACKEND

	// Ask which backend to use
	const backendList = (await FileFinder.find('directory', 'back/*', { cwd : __dirname })).map( fileEntity => fileEntity.name )
	if (!selectedBackend)
		selectedBackend = await askList(`Select backend to install`, backendList, { returnType: 'value' })

	// Copy backend sources to current directory
	const backendLoader = printLoaderLine(`Copying ${selectedBackend} files`)
	const sourceFiles = await FileFinder.find('all', '*', {
		cwd: path.join(__dirname, 'back', selectedBackend)
	})
	// console.log( sourceFiles );
	for ( const fileEntity of sourceFiles )
		await fileEntity.copyTo( process.cwd(), true )
	backendLoader(`${selectedBackend} files copied`)

	// Rename all .gitignore.template to .gitignore files
	const renameLoader = printLoaderLine(`Renaming .gitignore files`);
	(await FileFinder.find('file', '**/.gitignore.template')).map( async file => {
		await file.moveTo('.gitignore')
	})
	renameLoader(`Renamed .gitignore files`)

	// Execute install.js and inject dependencies
	const installer = require( path.join(process.cwd(), 'install') )
	installer.injectDependencies( module.exports )

	// Before questions
	installer.beforeQuestions && await installer.beforeQuestions()

	// Ask all questions
	let answers = {}
	const questions = await installer.getQuestions();
	const keys = Object.keys( questions )
	for ( const key of keys ) {
		// Ask question
		const question = questions[ key ]
		let answer
		let defaultValue = question.defaultValue
		if ( key in preferences )
			defaultValue = preferences[ key ]
		else if ( question.defaultValue && question.defaultValue.indexOf('$') === 0 )
			defaultValue = answers[ question.defaultValue.substr(1, question.defaultValue.length) ]

		// As input
		if ( question.input ) {
			answer = await askInput( question.input, {
				isNumber: question.isNumber,
				notEmpty: question.notEmpty,
				defaultValue
			})
		}
		// As list
		// TODO
		// else if ( question.list )
		// 	await askList()

		if ( question.check ) {
			if ( !(await question.check( answer )) ) {
				// TODO : Repeat question
			}
		}

		// Save before filter
		if ( question.save ) {
			preferences[ key ] = answer
			preferences.save()
		}

		// Filter answer
		answers[ key ] = ( question.filter ? question.filter( answer ) : answer )
	}

	// Filter answers
	answers = ( installer.filterAnswers ? await installer.filterAnswers( answers ) : answers )

	// Template
	const templateLoader = printLoaderLine(`Templating files ...`)
	installer.beforeTemplate && await installer.beforeTemplate( answers )
	const templatedFiles = installer.getFilesToTemplate( answers )
	for ( const filePath of templatedFiles ) {
		const fileToTemplate = new File( filePath )
		const resultLog = await fileToTemplate.load()
		// console.log({filePath, resultLog})
		if ( resultLog !== "loaded" ) {
			templateLoader(`Unable to load file ${filePath}`, 'error')
			process.exit(1)
		}
		fileToTemplate.template( answers )
		await fileToTemplate.save()
	}
	installer.afterTemplate && await installer.afterTemplate( answers )
	templateLoader(`${templatedFiles.length} file${templatedFiles.length > 1 ? 's' : ''} templated`)

	// Install
	installer.install && await installer.install( answers )

	// Remove installer
	const installerFile = await File.create('install.js')
	installerFile.delete()

	// ------------------------------------------------------------------------- SELECT FRONTEND
	// TODO

})()
