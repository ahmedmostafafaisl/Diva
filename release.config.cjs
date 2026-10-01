// /**
//  * @type {import('semantic-release').GlobalConfig}
//  */
// module.exports = {

//   branches: [
//     'main', 
//     {
//       name: 'preprod',
//       prerelease: 'beta' 
//     }
//   ],
  

//   plugins: [
//     [
//       '@semantic-release/commit-analyzer',
//       {
//         releaseRules: [
//           { type: 'fix', release: 'patch' }, 
//           { type: 'feat', release: 'minor' },  
//           { type: 'major', release: 'major' }, 
//         ],
//       },
//     ],
//     '@semantic-release/release-notes-generator',  
//     '@semantic-release/github',  
//   ],


//   tagFormat: 'v${version}',  };
