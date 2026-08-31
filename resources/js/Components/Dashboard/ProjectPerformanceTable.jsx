import { Link } from '@inertiajs/react';
import { Minus, TrendingDown, TrendingUp } from 'lucide-react';
import EmptyState from '@/Components/Shared/EmptyState';
import { formatNumber } from '@/utils/formatters';
import { cn } from '@/utils/helpers';
import CardFooterLink from './CardFooterLink';
import GradeBadge, { gradeStyle } from './GradeBadge';

const TRENDS = {
    improving: { icon: TrendingUp, label: 'Improving', className: 'text-success' },
    stable: { icon: Minus, label: 'Stable', className: 'text-slate-500' },
    declining: { icon: TrendingDown, label: 'Declining', className: 'text-warning' },
    critical: { icon: TrendingDown, label: 'Critical', className: 'text-danger' },
};

const CRITERIA_COLUMNS = ['workforce', 'arrival', 'journey', 'lms'];

export default function ProjectPerformanceTable({ projects = [], href }) {
    return (
        <div className="card flex h-full flex-col p-4">
            <h3 className="section-title">Company Performance by Project</h3>

            {projects.length === 0 ? (
                <EmptyState
                    className="py-6"
                    title="No projects rated"
                    description="Add a major project to start scoring performance."
                />
            ) : (
                <div className="mt-2 min-h-0 flex-1 overflow-x-auto">
                    <table className="w-full text-left">
                        <thead>
                            <tr className="border-b border-slate-200 text-[8px] font-medium text-slate-500">
                                <th className="py-1 pr-2 font-medium">Major Project</th>
                                <th className="px-1 py-1 text-center font-medium">Overall Rating</th>
                                <th className="px-1 py-1 text-center font-medium">Workforce</th>
                                <th className="px-1 py-1 text-center font-medium">Arrival</th>
                                <th className="px-1 py-1 text-center font-medium">Journey</th>
                                <th className="px-1 py-1 text-center font-medium">LMS</th>
                                <th className="px-1 py-1 text-center font-medium">Trend</th>
                                <th className="py-1 pl-1 text-right font-medium">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {projects.map((project) => {
                                const trend = TRENDS[project.trend] || TRENDS.stable;
                                const TrendIcon = trend.icon;

                                return (
                                    <tr key={project.id}>
                                        <td className="py-1.5 pr-2">
                                            <div className="flex items-start gap-1.5">
                                                <span
                                                    className={cn(
                                                        'mt-1 h-1.5 w-1.5 shrink-0 rounded-full',
                                                        gradeStyle(project.grade).dot,
                                                    )}
                                                />
                                                <div className="min-w-0">
                                                    <Link
                                                        href={route('major-projects.show', project.id)}
                                                        className="block truncate text-[10px] font-medium text-brand hover:underline"
                                                    >
                                                        {project.name}
                                                    </Link>
                                                    <span className="block text-[9px] text-slate-500">
                                                        {formatNumber(project.workers)} workers
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-1 py-1.5 text-center">
                                            <GradeBadge grade={project.grade} size="sm" />
                                        </td>
                                        {CRITERIA_COLUMNS.map((key) => (
                                            <td key={key} className="px-1 py-1.5 text-center text-[10px]">
                                                <GradeBadge
                                                    grade={project.criteria?.[key]}
                                                    variant="text"
                                                />
                                            </td>
                                        ))}
                                        <td className="px-1 py-1.5">
                                            <span
                                                className={cn(
                                                    'flex items-center justify-center gap-0.5 text-[9px] font-medium',
                                                    trend.className,
                                                )}
                                            >
                                                <TrendIcon className="h-2.5 w-2.5" />
                                                {trend.label}
                                            </span>
                                        </td>
                                        <td className="py-1.5 pl-1 text-right text-[9px] text-slate-500">
                                            {project.updated_at}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            <CardFooterLink href={href}>View all projects</CardFooterLink>
        </div>
    );
}
