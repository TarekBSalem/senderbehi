import { Link, usePage } from '@inertiajs/react';
import { dashboard, login, register } from '@/routes';
import AppLogoIcon from '@/components/app-logo-icon';

export default function PublicLayout({ children }: { children: React.ReactNode }) {
    const { auth } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <header className="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
                    <Link href="/" className="flex items-center gap-2 text-xl font-bold">
                        <AppLogoIcon className="size-6" />
                        SenderBehi
                    </Link>
                    <nav className="hidden items-center gap-6 text-sm font-medium md:flex">
                        <Link href="/" className="text-muted-foreground transition-colors hover:text-foreground">Home</Link>
                        <Link href="/pricing" className="text-muted-foreground transition-colors hover:text-foreground">Pricing</Link>
                        <Link href="/features" className="text-muted-foreground transition-colors hover:text-foreground">Features</Link>
                        <Link href="/docs" className="text-muted-foreground transition-colors hover:text-foreground">Docs</Link>
                        <Link href="/blog" className="text-muted-foreground transition-colors hover:text-foreground">Blog</Link>
                        <Link href="/contact" className="text-muted-foreground transition-colors hover:text-foreground">Contact</Link>
                    </nav>
                    <div className="flex items-center gap-2">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-flex h-9 items-center justify-center rounded-md px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>
            <main className="flex-1">
                {children}
            </main>
            <footer className="border-t bg-muted/50">
                <div className="mx-auto max-w-7xl px-4 py-12">
                    <div className="grid grid-cols-1 gap-8 md:grid-cols-4">
                        <div>
                            <div className="mb-4 flex items-center gap-2 text-lg font-bold">
                                <AppLogoIcon className="size-5" />
                                SenderBehi
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Send beautiful marketing emails that convert.
                            </p>
                        </div>
                        <div>
                            <h3 className="mb-3 font-semibold">Product</h3>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><Link href="/pricing" className="transition-colors hover:text-foreground">Pricing</Link></li>
                                <li><Link href="/features" className="transition-colors hover:text-foreground">Features</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="mb-3 font-semibold">Company</h3>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><Link href="/contact" className="transition-colors hover:text-foreground">Contact</Link></li>
                                <li><Link href="/privacy" className="transition-colors hover:text-foreground">Privacy Policy</Link></li>
                                <li><Link href="/terms" className="transition-colors hover:text-foreground">Terms of Service</Link></li>
                            </ul>
                        </div>
                        <div>
                            <h3 className="mb-3 font-semibold">Resources</h3>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><Link href="/docs" className="transition-colors hover:text-foreground">Documentation</Link></li>
                                <li><Link href="/blog" className="transition-colors hover:text-foreground">Blog</Link></li>
                                <li><Link href="/changelog" className="transition-colors hover:text-foreground">Changelog</Link></li>
                            </ul>
                        </div>
                    </div>
                    <div className="mt-8 border-t pt-8 text-center text-sm text-muted-foreground">
                        &copy; {new Date().getFullYear()} SenderBehi. All rights reserved.
                    </div>
                </div>
            </footer>
        </div>
    );
}
