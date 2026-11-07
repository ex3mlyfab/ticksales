import type { SVGAttributes } from 'react';

/**
 * The Sokora brand mark: an electric-lime "S" drawn as one continuous
 * rounded stroke — steady, geometric and unmistakably not a template
 * cipher. Colour follows the current text colour so the mark works on
 * Deep Forest tiles, ivory canvases and dark Mode surfaces alike.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M31 11H16.5C13.4 11 11.5 13.4 11.5 16.5S13.4 22 16.5 22H23.5C26.6 22 28.5 24.4 28.5 27.5S26.6 33 23.5 33H9"
                stroke="currentColor"
                strokeWidth={6}
                strokeLinecap="round"
                style={{ fill: 'none' }}
            />
        </svg>
    );
}
