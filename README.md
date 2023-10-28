# Multitasking in PHP with Fibers

This is an excercise to illustrate how PHP fibers can increase application performance with multitasking.
This code is an addition to my talk at Kilo Dev.X event in October 2023.

## Application description

Application is a PHP script that converts `*.tiff` images to `*.jpeg` format. 
Conversion is done using ImageMagick's `convert` utility.

## Installation pre-requisites

Make sure you have ImageMagick installed. 
On Mac you can use homebrew: 
```bash
brew install imagemagick
```
On linux, use your package manager, e.g. apt in Debian/Ubuntu: 
```bash
sudo apt install imagemagick
```
To verify ImageMagick installation run the following command:
```bash
convert logo: logo.gif
```

## Usage

Repository contains multiple branches, each containing the application at different stage:
- `main` branch contains a naive, synchronous implementation of converter
- `1--sync-nonblocking-refactor` branch: the application is still synchronous but contains some changes and optimizations for running asynchronously
- `2--async-fibers` branch: application is refactored to run asynchronously using fibers
- `3--optimize-performance` branch: limited the number of fibers to optimize application performance in long-run

## Comparing Sync vs Async performance

### Sync
```bash
~/Projects/fibers> php src/convert.php
Successfully converted ./photos/DSCF3727.tiff => ./photos/export/DSCF3727.jpg
Successfully converted ./photos/DSCF3706.tiff => ./photos/export/DSCF3706.jpg
Successfully converted ./photos/DSCF3700.tiff => ./photos/export/DSCF3700.jpg
Successfully converted ./photos/DSCF3703.tiff => ./photos/export/DSCF3703.jpg
Successfully converted ./photos/DSCF3714.tiff => ./photos/export/DSCF3714.jpg
Successfully converted ./photos/DSCF3699.tiff => ./photos/export/DSCF3699.jpg
Directory processed in 3.7 seconds
```

### Async
```bash
~/Projects/fibers> php src/convert.php
Successfully converted ./photos/DSCF3727.tiff => ./photos/export/DSCF3727.jpg
Successfully converted ./photos/DSCF3703.tiff => ./photos/export/DSCF3703.jpg
Successfully converted ./photos/DSCF3714.tiff => ./photos/export/DSCF3714.jpg
Successfully converted ./photos/DSCF3700.tiff => ./photos/export/DSCF3700.jpg
Successfully converted ./photos/DSCF3696.tiff => ./photos/export/DSCF3696.jpg
Successfully converted ./photos/DSCF3706.tiff => ./photos/export/DSCF3706.jpg
Successfully converted ./photos/DSCF3699.tiff => ./photos/export/DSCF3699.jpg
Directory processed in 1.5 seconds
```
