import { ChevronDown } from 'lucide-react';

import Reveal from '@/components/landing/reveal';

const questions = [
    {
        question: 'How does the free trial work?',
        answer: 'Every new workspace starts with a trial period, so you can load your catalogue, ring up a few test sales and get a feel for the reports before choosing a plan.',
    },
    {
        question: 'Can I use Sokora on more than one device?',
        answer: 'Yes. The web app runs in any modern browser, and the installable progressive web app keeps selling on a tablet or phone even when the connection drops.',
    },
    {
        question: 'Do I need separate accounts for each branch?',
        answer: 'No. Branches live inside a single workspace with their own stock, registers and reports, while roles and permissions decide what each team member can see.',
    },
    {
        question: 'What happens to my data if I change plan?',
        answer: 'Your catalogue, sales and stock stay exactly where they are. Moving between plans changes limits such as how many branches you can run, not your records.',
    },
    {
        question: 'Is there a cost to get started?',
        answer: 'There is no setup fee. You can explore the platform with the included demo data, then load your own products whenever you are ready.',
    },
];

/**
 * Frequently asked questions, using native disclosure elements.
 */
export default function LandingFaq() {
    return (
        <section
            id="faq"
            className="scroll-mt-24 border-t border-border py-20 lg:py-28"
        >
            <div className="mx-auto w-full max-w-3xl px-5 sm:px-8">
                <Reveal className="text-center">
                    <p className="text-sm font-semibold tracking-wider text-commerce uppercase dark:text-commerce">
                        Questions
                    </p>
                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        Frequently asked questions
                    </h2>
                </Reveal>

                <div className="mt-12 space-y-3">
                    {questions.map((item, index) => (
                        <Reveal key={item.question} delay={index * 50}>
                            <details className="group rounded-xl border border-border bg-card px-5 py-4 transition-colors open:bg-muted/40">
                                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-medium marker:content-none">
                                    {item.question}
                                    <ChevronDown className="size-4 shrink-0 text-muted-foreground transition-transform duration-200 group-open:rotate-180" />
                                </summary>
                                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                    {item.answer}
                                </p>
                            </details>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}
