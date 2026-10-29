import { Link as InertiaLink } from '@inertiajs/react';

import type { InertiaLinkProps } from '@inertiajs/react';

function Link(props: InertiaLinkProps) {
    return <InertiaLink {...props} />;
}

export default Link;
